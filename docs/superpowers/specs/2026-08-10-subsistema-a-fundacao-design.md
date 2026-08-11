# Subsistema A — Fundação / Correções Críticas — Design

**Data:** 2026-08-10
**Status:** Aprovado (design) — aguardando revisão do spec
**Aplicação:** app-vendas (Laravel 12 / PHP 8.2)

## Contexto

A aplicação é um pipeline de catálogo → publicação no Mercado Livre, com
multi-empresa (multi-tenant single-database por `company_id`) e IA para
descrições. A auditoria de integridade identificou riscos que devem ser
corrigidos **antes** de construir qualquer feature nova (vendas reais, análise,
rotinas autônomas, novos marketplaces — subsistemas B a E).

Este é o primeiro de cinco subsistemas, na ordem A → B → C → D → E. Cada
subsistema tem seu próprio ciclo spec → plano → implementação.

## Problemas confirmados (evidência no código)

1. **Vazamento entre empresas (real).** Em `app/Http/Controllers/Panel/ImportUIController.php`
   os métodos `show`, `errors`, `exportErrors`, `processProducts`, `destroy`,
   `destroyItem` usam `DB::table(...)->find($id)` **sem filtrar `company_id`**.
   Um usuário autenticado da empresa A consegue ver e apagar dados da empresa B
   trocando o ID na URL. O mesmo ocorre em `App\Http\Controllers\ImportController::convertWithoutAI`.
2. **Job quebrado disparado em massa.** `app/Jobs/ImportSupplierFile.php:150-155`
   dispara `EnrichProduct` para cada linha importada. `app/Jobs/EnrichProduct.php:20`
   é um stub que chama `PublishListingToML::dispatch($this->sku)` com assinatura
   incompatível (o job espera `(int productId, int userId)`). Resultado: toda
   importação enche a fila de jobs falhos e tenta auto-publicar no ML sem o
   lojista pedir.
3. **Endpoints sem autenticação.** `routes/web.php:111-112`: `/import/supplier`
   (duplica o upload autenticado do painel) e `/import/convert-without-ai`
   (chamado de uma página autenticada — `resources/views/panel/imports/show.blade.php:243`).
4. **Rota de onboarding inexistente.** `EnsureUserHasCompany` redireciona para
   `panel.companies.setup`, que não existe em `routes/web.php` → erro de rota.
5. **Higiene do repositório.** ~19 scripts soltos versionados na raiz
   (`test_*.php`, `check_*.php`, `debug_attributes.php`), além de `nul`,
   `temp_original.txt`, `php_errors.log`. Testes automatizados reais inexistentes
   (só os dois `ExampleTest` padrão).

## Decisões tomadas (brainstorming)

- **Isolamento multi-tenant:** refatoração completa para Eloquent + Global Scope
  (não apenas remendos manuais).
- **`/import/supplier`:** remover (ninguém externo depende dele).
- **Pós-importação:** manual — importar apenas popula `products_raw`; o lojista
  seleciona, processa com IA e publica manualmente. Remover `EnrichProduct` e o
  auto-dispatch.

## Objetivo e escopo

Blindar a base. Entregas:

1. Isolamento multi-tenant automático via trait + Global Scope.
2. Pipeline de importação consolidado, sem jobs quebrados nem auto-publicação.
3. Correções pontuais de segurança e rotas.
4. Limpeza do repositório.
5. Testes que provam o isolamento entre empresas.

**Fora de escopo:** captura de vendas reais, análise/dashboard, scheduler/cron,
novos marketplaces (subsistemas B–E).

## Arquitetura da solução

### 1. Isolamento multi-tenant (núcleo)

**Trait `App\Models\Concerns\BelongsToCompany`:**

- Registra um Global Scope que adiciona `where('company_id', <empresa atual>)`
  em toda query do model.
- No evento `creating`, preenche `company_id` automaticamente com a empresa
  atual quando não informado.
- Expõe escape controlado (`Model::withoutCompanyScope()`) para código de
  sistema que legitimamente roda sem usuário logado (webhooks, jobs de sistema),
  sempre passando `company_id` explícito.

**Resolver de empresa atual — `App\Support\CurrentCompany` (singleton):**

- Fonte da verdade sobre qual é a empresa ativa na request/job.
- Em contexto web: alimentado pelo middleware `EnsureUserHasCompany`
  (`users.current_company_id`).
- Em contexto de job: recebe `company_id` explícito no dispatch.
- Remove o acoplamento atual em que `CompanyHelper` instancia um Controller
  (`app(IntegrationController::class)`) para obter contexto/tokens.

**Models que recebem o trait** (tabelas que já têm `company_id`):
`Product`, `SupplierImport`, `Supplier`, `Order`, `Listing`. `MercadoLivreListing`,
`ProductImage`, `ProductIntegration` continuam acessados via relação do pai
(`Product`) já escopado.

**Tabelas-filho consultadas soltas por ID** (`products_raw`, `import_errors`):
recebem `company_id` via nova migration e passam a usar o trait, porque hoje são
consultadas diretamente (`show`, `destroyItem`, `convertWithoutAI`) e vazam.
Migration de backfill preenche `company_id` a partir do `supplier_import`
correspondente.

**Migração `DB::table()` → Eloquent** nos caminhos de tenant (controllers de
import, produtos, fornecedores; jobs de import e IA). Necessária porque
`DB::table` ignora o Global Scope. Este é o maior item de esforço e o de maior
risco de regressão.

### 2. Consolidação do pipeline de importação

- Remover o job `EnrichProduct` (`app/Jobs/EnrichProduct.php`) e o loop de
  auto-dispatch em `ImportSupplierFile.php:150-155`. Importar passa a apenas
  popular `products_raw`.
- Remover a rota `/import/supplier` e o método `ImportController::store`.
- Mover `convert-without-ai` para o grupo `panel`/`auth`; convertê-lo para
  Eloquent com escopo de empresa e validar que o `products_raw` alvo pertence à
  empresa atual. Avaliar se `App\Http\Controllers\ImportController` ainda é
  necessário; se sobrar só `convertWithoutAI`, mover a lógica para
  `Panel\ImportUIController` e remover o controller antigo.
- Fluxo final único: importar → revisar → `ProcessProductWithAI` (manual) →
  publicar (manual).

### 3. Correções pontuais + higiene

- **Onboarding:** `EnsureUserHasCompany` passa a redirecionar para
  `panel.companies.create` (fluxo existente) em vez da inexistente
  `panel.companies.setup`.
- **Higiene do repo:** remover do versionamento os scripts soltos (`test_*.php`,
  `check_*.php`, `debug_attributes.php`), `nul`, `temp_original.txt`,
  `php_errors.log`; adicionar padrões ao `.gitignore`. Scripts de diagnóstico
  úteis serão reescritos como comandos Artisan no subsistema D ou descartados.

### 4. Estratégia de testes

- Testes de feature que provam o isolamento: usuário da empresa A recebe **404**
  ao acessar `show`/`destroy`/`convert` de recurso da empresa B. Escritos antes
  do fix (TDD) — devem falhar contra o código atual, confirmando o bug.
- Teste de que importar **não** dispara publicação automática (`EnrichProduct`
  não existe mais; nenhum job de publicação é enfileirado no import).
- Teste do redirecionamento de onboarding para usuário sem empresa.
- Configurar SQLite in-memory (`phpunit.xml`) para a suíte.

## Componentes e interfaces

| Componente | Responsabilidade | Depende de |
|---|---|---|
| `BelongsToCompany` (trait) | Global scope + auto-fill de `company_id` | `CurrentCompany` |
| `CurrentCompany` (singleton) | Resolver a empresa ativa (web/job) | sessão/usuário; `company_id` explícito em jobs |
| `EnsureUserHasCompany` (middleware) | Selecionar empresa ativa; alimentar `CurrentCompany`; redirecionar onboarding | `CurrentCompany` |
| Migration `add_company_id_to_child_tables` | `company_id` em `products_raw`, `import_errors` + backfill | — |
| `ImportUIController` (refatorado) | Import/listagem/conversão via Eloquent escopado | Models com trait |

## Riscos e mitigação

- **Regressão na migração `DB::table` → Eloquent (alto).** Mitigação: migrar por
  caminho (import → produtos → fornecedores), com testes de feature cobrindo
  cada caminho antes de refatorar; commits pequenos e revisáveis.
- **Global Scope quebrar jobs/webhooks de sistema.** Mitigação: `withoutCompanyScope()`
  explícito nesses pontos, com `company_id` passado no dispatch.
- **Backfill de `company_id` em registros órfãos** (products_raw sem
  supplier_import válido). Mitigação: a migration loga e ignora órfãos; testar
  em cópia do banco antes.

## Critérios de sucesso

- Usuário da empresa A não consegue acessar nem manipular dados da empresa B por
  nenhuma rota (provado por teste).
- Importar uma planilha não gera jobs falhos nem publica no ML automaticamente.
- Nenhuma query de caminho de tenant usa `DB::table` sem escopo.
- Repositório sem scripts soltos, logs ou arquivos temporários versionados.
- Suíte de testes roda em SQLite in-memory e passa.
