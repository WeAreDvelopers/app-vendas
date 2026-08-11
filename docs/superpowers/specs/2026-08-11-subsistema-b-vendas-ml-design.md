# Subsistema B — Ciclo de Vida de Vendas do Mercado Livre — Design

**Data:** 2026-08-11
**Status:** Aprovado (design) — aguardando revisão do spec
**Aplicação:** app-vendas (Laravel 12 / PHP 8.2+)
**Depende de:** Subsistema A (isolamento multi-tenant: `CurrentCompany`, `BelongsToCompany`, `CompanyScope`, `company_integrations`).

## Contexto

Segundo subsistema da sequência A→B→C→D→E. A auditoria confirmou que **nenhuma captura real de vendas funciona hoje**:

- `orders` tem apenas colunas mínimas (`id`, `company_id`, `ml_order_id`, `status`, `payload`, `label_url`, timestamps) — nenhum campo de venda.
- `order_items` existe no schema mas **nunca é escrita** (tabela morta; sem `company_id`).
- O webhook "real" (`Api\WebhookController::processOrder` → `saveOrder`) grava em **colunas inexistentes** (`total_amount`, `buyer_id`, `data`, `user_id`, …) e não preenche `payload` (NOT NULL) nem `company_id` → quebraria em execução.
- O job que grava nas colunas corretas (`HandleMLWebhook`) é **DEMO** e **não tem rota** (despachado só pelo `WebhookController` raiz órfão) → nunca roda.
- **Dois armazenamentos de token desconectados**: o webhook resolve o vendedor por `mercado_livre_tokens.ml_user_id`; o OAuth multi-empresa (subsistema A) guarda o `ml_user_id` enterrado no JSON de `company_integrations.credentials.user_id`, sem coluna consultável. Resultado: hoje **não há como atribuir um pedido à empresa certa**.
- `MercadoLivreService` **não tem** método para buscar pedidos (`GET /orders/{id}`, `/orders/search`).

## Decisões tomadas (brainstorming)

- **Modelo de dados:** colunas normalizadas e consultáveis em `orders` + `payload` bruto; `order_items` populada com `company_id` e link ao `Product`.
- **Vínculo empresa:** coluna `ml_user_id` indexada em `company_integrations`, preenchida no OAuth, como fonte única da verdade.
- **Escopo de tópicos:** tratar **todos** (`orders_v2`, `items`, `questions`, `claims`).
- **Histórico:** **backfill completo** (paginado, resiliente a rate-limit, idempotente, retomável).
- **Impressão:** **etiqueta real** do ML (ZPL), substituindo o ZPL hardcoded.
- **Objetivo geral:** sistema **completo**, não uma versão simples.

## Objetivo e escopo

Capturar, persistir e manter sincronizado o ciclo de vida do pedido do Mercado Livre, por empresa, de forma idempotente e escopada — destravando o subsistema C (análise). Seis módulos:

- **B1 — Modelo de dados** (fundação)
- **B2 — Vínculo empresa + intake de webhook** (correção/segurança)
- **B3 — Ingestão de pedidos `orders_v2`** (núcleo)
- **B4 — Backfill histórico completo**
- **B5 — Outros tópicos** (`items`, `questions`, `claims`)
- **B6 — Impressão real de etiqueta**

**Fora de escopo:** módulo de atendimento/CX completo (responder perguntas, gerir reclamações — só notificação+persistência bruta aqui); agendamento automático do backfill/sync (subsistema D); análise/dashboard (subsistema C); marketplaces além do ML (subsistema E).

## Arquitetura

### B1 — Modelo de dados

**Migration `add_sales_fields_to_orders`** adiciona a `orders`:
`total_amount` (decimal 12,2, nullable), `paid_amount` (decimal 12,2, nullable), `currency` (string 3, default 'BRL'), `buyer_ml_id` (string, nullable, indexado), `buyer_nickname` (string, nullable), `payment_status` (string, nullable), `shipping_status` (string, nullable), `shipment_id` (string, nullable, indexado), `date_closed` (timestamp, nullable, indexado — data efetiva da venda). Mantém `payload`, `company_id`, `ml_order_id` (unique), `label_url`.

**Status do pedido:** o enum atual (`paid/shipped/canceled/ready_to_print`) é insuficiente para os status reais do ML. Converter `orders.status` para **string livre** (guardando o status do ML tal como vem, ex.: `confirmed`, `payment_required`, `paid`, `shipped`, `delivered`, `cancelled`). Migration de alteração guardada por driver (MySQL `MODIFY`; no-op no SQLite dos testes — padrão já adotado no subsistema A).

**Migration `add_fields_to_order_items`** adiciona a `order_items`: `company_id` (FK companies, nullable, cascade) e `ml_item_id` (string, nullable, indexado). **Reutiliza a coluna `price` existente como preço unitário** (não adiciona `unit_price`). Mantém `order_id`, `product_id`, `title`, `qty`, `price`.

**Models:**
- `Order` (já existe, escopado): adicionar `hasMany(OrderItem)`, casts (`total_amount`/`paid_amount` decimal, `date_closed` datetime, `payload` array).
- `OrderItem` (novo): `use BelongsToCompany`, `belongsTo(Order)`, `belongsTo(Product)`, `$guarded=[]`.

### B2 — Vínculo empresa + intake de webhook

**Migration `add_ml_user_id_to_company_integrations`**: coluna `ml_user_id` (string, nullable, **indexada**). Preenchida no callback OAuth (`Panel\IntegrationController::mercadoLivreCallback`) a partir de `credentials['user_id']` (também mantido no JSON para compatibilidade). Backfill: para integrações ML existentes, popular `ml_user_id` a partir do JSON `credentials.user_id` quando presente.

**Resolver `CompanyResolver` (ou método em serviço):** `companyIdForMlUser(int|string $mlUserId): ?int` — consulta `company_integrations` (`integration_type = 'mercado_livre'`, `ml_user_id = ?`) e retorna `company_id`. Fonte única da verdade.

**Intake do webhook** (`Api\WebhookController::mercadoLivre`, POST `/api/webhooks/mercado-livre`):
1. Valida payload mínimo (`topic`, `resource`, `user_id`).
2. Responde **200 imediatamente** e **enfileira** um job por notificação (ML exige resposta rápida; timeouts geram reentrega). Não fazer chamadas de API na thread do request.
3. **Autenticidade:** o job aceita a notificação se `companyIdForMlUser(user_id)` resolve uma empresa e o `resource` é buscável com o token dessa empresa (posse de token válido = legítimo). Notificações de `user_id` desconhecido são logadas e descartadas. IPs do ML como verificação secundária. Remove o `return true` incondicional atual.

**Idempotência:** upsert por `ml_order_id` (unique). Reprocessar a mesma notificação não duplica pedido nem itens.

### B3 — Ingestão de pedidos `orders_v2` (núcleo)

**`MercadoLivreService::getOrder(int $companyId, string $orderId): ?array`** — GET `https://api.mercadolibre.com/orders/{id}` com token da empresa (auto-renovação via `getActiveTokenFromIntegration`). Novo método público; o service já tem o padrão de token/refresh.

**`OrderIngestionService::ingest(int $companyId, array $mlOrder): Order`** — mapeia o JSON do pedido do ML para o `Order` (colunas normalizadas + `payload` bruto) e seus `order_items`:
- Upsert do `Order` por `ml_order_id` dentro da empresa; grava `total_amount`, `paid_amount`, `currency`, `buyer_ml_id`/`buyer_nickname`, `payment_status` (de `payments[0].status`), `shipping_status`/`shipment_id` (de `shipping`), `date_closed`, `status` (status do ML).
- Para cada `order_items[]` do ML: upsert por (`order_id`, `ml_item_id`); resolve `product_id` local por SKU (`seller_sku`/`seller_custom_field`) ou `ml_item_id` quando houver anúncio local; grava `company_id` explícito.
- Dispara notificação in-app ("Nova venda no ML: pedido #X — R$ Y").
- **`company_id` sempre explícito** (worker roda sem `CurrentCompany`; lição do subsistema A). Leitura de entidades da empresa via `withoutCompanyScope()`/escopo explícito conforme necessário.

**Job `IngestMLOrder(int $companyId, string $orderId)`**: `ShouldQueue`, `tries`/backoff; chama `getOrder` + `OrderIngestionService::ingest`. Despachado pelo intake do webhook para `orders_v2` e pelo backfill (B4).

### B4 — Backfill histórico completo

**Comando `php artisan ml:backfill-orders {company?} {--from=} {--to=}`** + **Job `BackfillMLOrders(int $companyId, ...)`**:
- Pagina `GET /orders/search?seller={ml_user_id}&order.date_created.from=&order.date_created.to=&sort=date_asc&offset=&limit=50`.
- **Contorna o teto de offset** da API paginando por **janelas de data** (avança a janela quando aproxima do limite de offset).
- **Rate limit:** respeita limites do ML com pausas/backoff exponencial em 429; `limit` moderado.
- **Idempotente:** cada pedido passa pelo `OrderIngestionService` (upsert por `ml_order_id`) — reexecutar não duplica.
- **Retomável:** registra progresso por empresa (último `date_created` processado) para poder continuar de onde parou.
- Reusa `getOrder`/`OrderIngestionService`. Execução manual/sob demanda; agendamento é subsistema D.

### B5 — Outros tópicos (tratamento enxuto)

- **`items`**: resolve a empresa por `user_id`, busca `GET /items/{id}` e atualiza o `mercado_livre_listings` da empresa (status/estoque/preço) — mantém o anúncio local em sincronia. Escopado por empresa.
- **`questions`**: busca `GET /questions/{id}`; se sem resposta, gera notificação in-app para o lojista. Persistência mínima do bruto (na notificação/log); **sem** módulo de resposta.
- **`claims`**: busca o recurso; gera notificação in-app (urgente). Persistência mínima; **sem** módulo de gestão de reclamação.
- Cada tópico é processado no seu próprio job (ou um job de despacho por tópico), respondendo 200 no intake.

### B6 — Impressão real de etiqueta

- Novo método **`MercadoLivreService::getShipmentLabel(int $companyId, string $shipmentId, string $type = 'zpl2'): ?string`** — GET `/shipment_labels?shipment_ids={id}&response_type={zpl2|pdf}`. Retorna o conteúdo real da etiqueta.
- **Regra de disparo explícita** (não em toda notificação): quando um pedido atinge estado imprimível (ex.: pago/pronto para envio com `shipment_id`), enfileira `PrintJob` com o **ZPL real** (fim do ZPL hardcoded). O agente de impressão existente (`/api/print/*`, `printagent.token`) consome `print_jobs`.
- `PrintJob` reescrito para receber o pedido/etiqueta reais em vez do payload demo.

### B7 — Limpeza + testes

**Limpeza:**
- Remover o `app/Http/Controllers/WebhookController.php` raiz (órfão) e o job `HandleMLWebhook` (DEMO).
- Reescrever `Api\WebhookController::saveOrder` (quebrado) → delega ao `OrderIngestionService`. O `Api\WebhookController` fica fino (valida + enfileira).

**Testes (SQLite in-memory; API do ML via `Http::fake`):**
- Resolução empresa por `ml_user_id` (incl. `user_id` desconhecido → descartado).
- Ingestão idempotente: a mesma notificação/pedido processado 2× resulta em **1** `Order` e itens corretos, com `company_id` certo.
- Mapeamento de campos normalizados a partir de um payload real de exemplo do ML.
- Isolamento multi-tenant: pedido/itens da empresa A não aparecem para B (reusa o padrão de testes do subsistema A).
- Webhook responde **200** e **enfileira** (via `Bus::fake`/`Queue::fake`) sem chamar API na thread do request.
- Backfill paginado por janelas de data (com `Http::fake` simulando páginas e um 429) — idempotente e retomável.
- `items`/`questions`/`claims`: atualização de listing e geração de notificação.
- Impressão: pedido imprimível enfileira `PrintJob` com ZPL real (mockado).

## Componentes e interfaces

| Componente | Responsabilidade | Depende de |
|---|---|---|
| Migrations B1/B2 | Colunas de venda em orders/order_items; `ml_user_id` em company_integrations | — |
| `OrderItem` (model) | Item de pedido escopado | `BelongsToCompany` |
| `MercadoLivreService::getOrder/getShipmentLabel` | Buscar pedido/etiqueta via API ML | token por empresa (company_integrations) |
| `CompanyResolver::companyIdForMlUser` | ml_user_id → company_id | company_integrations.ml_user_id |
| `OrderIngestionService::ingest` | Upsert idempotente de order+items + notificação | Order/OrderItem, Product |
| `IngestMLOrder` (job) | Orquestra fetch+ingestão de um pedido | getOrder, OrderIngestionService |
| `Api\WebhookController` (fino) | Validar + responder 200 + enfileirar por tópico | CompanyResolver, jobs |
| `BackfillMLOrders` (cmd+job) | Backfill histórico paginado/retomável/rate-limited | getOrder/search, OrderIngestionService |
| `PrintJob` (reescrito) | Enfileirar etiqueta ZPL real | getShipmentLabel |

## Riscos e mitigação

- **Formato real do payload de `/orders/{id}` e `/orders/search`.** Mitigação: mapear a partir de um payload de exemplo documentado; testes com `Http::fake` usando amostras realistas; tolerância a campos ausentes (nullable).
- **Rate limit / teto de offset no backfill.** Mitigação: janelas de data + backoff em 429 + `limit` moderado + retomabilidade.
- **Escopo por empresa em jobs (sem `CurrentCompany`).** Mitigação: sempre passar/gravar `company_id` explícito; `withoutCompanyScope()` para leituras de sistema (padrão do subsistema A).
- **Duplo fluxo OAuth (company_integrations vs mercado_livre_tokens).** Mitigação: B padroniza a resolução em `company_integrations.ml_user_id`; o fluxo legado continua funcionando, mas a ingestão de vendas não depende dele.
- **`orders.status` string livre** pode divergir de expectativas de UI. Mitigação: documentar o conjunto de valores do ML; C consome via colunas normalizadas, não pelo enum.

## Critérios de sucesso

- Uma notificação `orders_v2` real resulta em um `Order` + `order_items` persistidos, com campos normalizados corretos, atribuídos à **empresa certa**, e o lojista é notificado.
- Reprocessar a mesma notificação não duplica dados (idempotência provada por teste).
- O webhook responde 200 rapidamente e nunca chama a API do ML na thread do request.
- O backfill importa o histórico de uma conta de forma paginada, idempotente e retomável, respeitando rate limit.
- `items`/`questions`/`claims` produzem sincronização/notificação corretas e escopadas.
- Um pedido imprimível enfileira uma etiqueta ZPL **real** (não demo) para o agente de impressão.
- Nenhum dado de venda vaza entre empresas (isolamento provado por teste).
- O código morto/DEMO (`WebhookController` raiz, `HandleMLWebhook`, `saveOrder` quebrado) é removido/consertado.
