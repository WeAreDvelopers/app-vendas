# Sistema de Notificações em Tempo Real

## Visão Geral

Sistema de notificações em tempo real usando AJAX Polling, perfeito para servidores compartilhados que não suportam WebSockets.

## Características

- ✅ **Polling AJAX** a cada 10 segundos
- ✅ **Badge com contador** de notificações não lidas
- ✅ **Som de notificação** ao receber novas notificações
- ✅ **Pausa automática** quando aba está inativa (economiza recursos)
- ✅ **4 tipos de notificação**: success, info, warning, error
- ✅ **Ações customizadas** com links para páginas específicas
- ✅ **Marcar como lida** individual ou em massa
- ✅ **Remover notificações**
- ✅ **Formatação de tempo** relativa (ex: "5min atrás")
- ✅ **Prevenção XSS** com escape de HTML

## ⚠️ Importante: URLs Relativas

**SEMPRE use paths RELATIVOS ao invés de URLs absolutas ou route()!**

```php
// ✅ CORRETO - Path relativo funciona em qualquer domínio
"/panel/products/{$id}"

// ❌ ERRADO - URL absoluta com domínio fixo
url("/panel/products/{$id}")  // Gera: http://localhost/panel/products/1

// ❌ ERRADO - route() pode quebrar se renomear
route('panel.products.show', $id)
```

**Por quê?**
- URLs das notificações são **salvas no banco de dados**
- Paths relativos funcionam em **localhost, produção, qualquer domínio**
- `url()` e `route()` geram URLs com domínio fixo (ex: http://localhost)
- Se você mudar de domínio ou renomear rotas, os links quebram

## Como Usar

### 1. Enviar Notificação Simples

```php
use App\Helpers\NotificationHelper;

// Notificação de sucesso
NotificationHelper::success(
    'Título da Notificação',
    'Mensagem detalhada aqui'
);

// Notificação de erro
NotificationHelper::error(
    'Erro ao Processar',
    'Detalhes do erro aqui'
);

// Notificação de aviso
NotificationHelper::warning(
    'Atenção',
    'Algo requer sua atenção'
);

// Notificação informativa
NotificationHelper::info(
    'Informação',
    'Informação útil para o usuário'
);
```

### 2. Notificação com Ação (Link)

```php
// ✅ RECOMENDADO: Use path relativo (funciona em qualquer domínio)
NotificationHelper::success(
    'Importação Concluída',
    '150 produtos foram importados com sucesso!',
    "/panel/imports/{$importId}",  // Path relativo
    'Ver Detalhes'                 // Texto do botão
);

// ❌ EVITE: url() gera domínio fixo (http://localhost)
// url("/panel/imports/{$importId}")

// ❌ EVITE: route() pode quebrar ao renomear
// route('panel.imports.show', $importId)
```

### 3. Notificação para Usuário Específico

```php
NotificationHelper::success(
    'Tarefa Concluída',
    'Sua tarefa foi processada com sucesso',
    '/panel/dashboard',  // ✅ Path relativo
    'Ir para Dashboard',
    $userId  // ID do usuário (opcional, null = todos os usuários)
);
```

### 4. Helpers Específicos (Pré-configurados)

```php
// Importação concluída
NotificationHelper::importCompleted(
    $importId,
    $totalRows,
    $processedRows,
    $userId  // opcional
);

// Importação com erros
NotificationHelper::importWithErrors(
    $importId,
    $errorCount,
    $userId  // opcional
);

// Processamento de IA concluído
NotificationHelper::aiProcessingCompleted(
    $productCount,
    $userId  // opcional
);

// Produto publicado no ML
NotificationHelper::productPublished(
    $productId,
    $productName,
    $userId  // opcional
);

// Erro na publicação
NotificationHelper::publishError(
    $productName,
    $errorMessage,
    $userId  // opcional
);
```

## Exemplos de Integração nos Jobs

### ImportSupplierFile Job

```php
<?php
namespace App\Jobs;

use App\Helpers\NotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ImportSupplierFile implements ShouldQueue {
    use Queueable;

    public function __construct(public int $importId) {}

    public function handle(): void {
        $import = SupplierImport::find($this->importId);

        try {
            // Processa importação...
            $processedRows = $this->processFile($import);

            // Atualiza status
            $import->update(['status' => 'done']);

            // Envia notificação de sucesso
            // ✅ Use url() ao invés de route() para links salvos no banco
            NotificationHelper::success(
                'Importação Concluída',
                "Importação #{$import->id} finalizada! {$processedRows} produtos processados.",
                url("/panel/imports/{$import->id}"),
                'Ver Detalhes'
            );

        } catch (\Exception $e) {
            // Envia notificação de erro
            NotificationHelper::error(
                'Erro na Importação',
                "Falha ao processar importação #{$import->id}: {$e->getMessage()}",
                url("/panel/imports/{$import->id}"),
                'Ver Detalhes'
            );

            throw $e;
        }
    }
}
```

### ProcessProductWithAI Job

```php
<?php
namespace App\Jobs;

use App\Helpers\NotificationHelper;

class ProcessProductWithAI implements ShouldQueue {
    use Queueable;

    public function __construct(public array $productIds) {}

    public function handle(): void {
        $processedCount = 0;

        foreach ($this->productIds as $productId) {
            try {
                // Processa produto com IA
                $this->enrichProductWithAI($productId);
                $processedCount++;
            } catch (\Exception $e) {
                \Log::error("Erro ao processar produto {$productId}: {$e->getMessage()}");
            }
        }

        // Notifica conclusão do processamento
        if ($processedCount > 0) {
            NotificationHelper::aiProcessingCompleted($processedCount);
        }
    }
}
```

### PublishListingToML Job

```php
<?php
namespace App\Jobs;

use App\Helpers\NotificationHelper;

class PublishListingToML implements ShouldQueue {
    use Queueable;

    public function __construct(public int $productId) {}

    public function handle(): void {
        $product = Product::find($this->productId);

        try {
            // Publica no Mercado Livre
            $mlItemId = $this->publishToMercadoLivre($product);

            // Atualiza produto
            $product->update(['status' => 'published']);

            // Notifica sucesso
            NotificationHelper::productPublished(
                $product->id,
                $product->name
            );

        } catch (\Exception $e) {
            // Notifica erro
            NotificationHelper::publishError(
                $product->name,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
```

## Configuração do Polling

O intervalo de polling pode ser ajustado no arquivo `resources/views/layouts/panel.blade.php`:

```javascript
const POLLING_INTERVAL = 10000; // 10 segundos (10000 ms)
const NOTIFICATION_SOUND_ENABLED = true; // Ativar/desativar som
```

### Recomendações de Intervalo:

- **5 segundos** (5000ms) - Atualizações muito frequentes (maior carga no servidor)
- **10 segundos** (10000ms) - ✅ **Recomendado** - Bom balanço
- **30 segundos** (30000ms) - Menos carga, notificações mais lentas
- **60 segundos** (60000ms) - Para servidores muito limitados

## Otimizações de Performance

1. **Pausa Automática**: O polling para quando a aba do navegador fica inativa
2. **Prevenção de Chamadas Duplicadas**: Flag `isPolling` evita requisições simultâneas
3. **Limite de Notificações**: API retorna apenas 20 notificações mais recentes
4. **Índice no Banco**: Índice composto em `(user_id, read, created_at)` para queries rápidas
5. **Limpeza Automática**: Notificações com mais de 7 dias não são exibidas

## API Endpoints

```
GET    /panel/notifications              - Lista notificações não lidas
POST   /panel/notifications/{id}/read    - Marca notificação como lida
POST   /panel/notifications/read-all     - Marca todas como lidas
DELETE /panel/notifications/{id}         - Remove notificação
```

## Banco de Dados

### Estrutura da Tabela `notifications`

```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,              -- NULL = notificação global
    type VARCHAR(255),                 -- success, info, warning, error
    title VARCHAR(255),
    message TEXT,
    icon VARCHAR(255) NULL,            -- Bootstrap Icons (opcional)
    action_url VARCHAR(255) NULL,      -- URL do botão de ação
    action_text VARCHAR(255) NULL,     -- Texto do botão
    read BOOLEAN DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    KEY idx_notifications (user_id, read, created_at)
);
```

## Interface do Usuário

### Badge de Notificações
- Aparece no canto superior direito
- Mostra número de notificações não lidas
- Vermelho para chamar atenção
- Máximo exibido: "99+"

### Dropdown de Notificações
- Lista de notificações mais recentes
- Ícone colorido por tipo
- Tempo relativo de criação
- Botão de ação (se configurado)
- Opções: Marcar como lida / Remover

### Som de Notificação
- Beep curto usando Web Audio API
- Toca apenas para NOVAS notificações
- Pode ser desabilitado via configuração

## Testes

### Criar Notificação de Teste via Tinker

```bash
php artisan tinker
```

```php
// Importar helper
use App\Helpers\NotificationHelper;

// Criar notificação de teste
NotificationHelper::success(
    'Teste de Notificação',
    'Esta é uma notificação de teste criada via Tinker!',
    route('panel.dashboard'),
    'Ir para Dashboard'
);

// Criar várias notificações
for ($i = 1; $i <= 5; $i++) {
    NotificationHelper::info(
        "Notificação #{$i}",
        "Esta é a notificação número {$i}",
    );
}
```

## Troubleshooting

### Notificações não aparecem?

1. Verifique se está logado
2. Abra o console do navegador (F12) e procure por erros
3. Verifique se a rota `/panel/notifications` está funcionando
4. Confirme que a migration foi executada: `php artisan migrate`

### Badge não atualiza?

1. Verifique o intervalo de polling (padrão: 10 segundos)
2. Veja se há erros no console
3. Confirme que o JavaScript está sendo carregado

### Performance lenta?

1. Aumente o intervalo de polling (ex: 30 segundos)
2. Limite o número de usuários simultâneos
3. Use cache para a query de notificações
4. Configure índices adequados no banco

## Vantagens desta Solução

✅ **Funciona em servidor compartilhado** (não requer WebSockets)
✅ **Simples de implementar** e manter
✅ **Baixo consumo de recursos** com otimizações
✅ **Compatível com todos os navegadores** modernos
✅ **Sem dependências externas** (Redis, Pusher, etc)
✅ **Código limpo e documentado**

## Próximas Melhorias Sugeridas

- [ ] Categorias de notificações (importações, produtos, vendas)
- [ ] Filtros por tipo
- [ ] Paginação na lista de notificações
- [ ] Exportar notificações antigas
- [ ] Email quando há notificação urgente
- [ ] Push notifications (browser)
