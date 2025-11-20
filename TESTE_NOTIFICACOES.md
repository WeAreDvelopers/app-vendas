# Como Testar o Sistema de Notificações

## Método 1: Via Tinker (Recomendado)

```bash
php artisan tinker
```

### Teste 1: Notificação de Sucesso

```php
use App\Helpers\NotificationHelper;

NotificationHelper::success(
    'Teste de Sucesso',
    'Esta é uma notificação de sucesso! O sistema está funcionando perfeitamente.',
    route('panel.dashboard'),
    'Ir para Dashboard'
);
```

### Teste 2: Notificação de Importação Concluída

```php
NotificationHelper::importCompleted(1, 150, 145);
```

### Teste 3: Múltiplas Notificações

```php
// Cria 5 notificações de tipos diferentes
NotificationHelper::success('Sucesso 1', 'Operação concluída com sucesso');
NotificationHelper::info('Informação', 'Aqui está uma informação importante');
NotificationHelper::warning('Atenção', 'Isto requer sua atenção');
NotificationHelper::error('Erro', 'Algo deu errado');
NotificationHelper::success('Sucesso 2', 'Outra operação concluída');
```

### Teste 4: Notificação com Link

```php
NotificationHelper::success(
    'Produto Atualizado',
    'O produto foi atualizado com sucesso',
    route('panel.products.index'),
    'Ver Produtos'
);
```

## Método 2: Criar Rota de Teste Temporária

Adicione isto no arquivo `routes/web.php` (apenas para testes):

```php
// APENAS PARA TESTES - REMOVER EM PRODUÇÃO
Route::get('/test/notification', function() {
    \App\Helpers\NotificationHelper::success(
        'Notificação de Teste',
        'Esta notificação foi criada ao acessar /test/notification',
        route('panel.dashboard'),
        'Ver Dashboard'
    );

    return 'Notificação criada! Verifique o sino no canto superior direito.';
})->middleware('auth');
```

Depois acesse: `http://seu-site.com/test/notification`

## Método 3: Integrar em um Job Existente

Edite um job existente e adicione notificações. Exemplo no `ImportSupplierFile`:

```php
// No final do método handle(), adicione:

use App\Helpers\NotificationHelper;

// Após processar com sucesso
NotificationHelper::importCompleted(
    $import->id,
    $import->total_rows,
    $this->validRows
);

// Se houver erros
if (count($this->errors) > 0) {
    NotificationHelper::importWithErrors(
        $import->id,
        count($this->errors)
    );
}
```

## Verificando se está Funcionando

1. **Login**: Faça login na aplicação
2. **Crie uma notificação** usando um dos métodos acima
3. **Aguarde até 10 segundos** (intervalo de polling)
4. **Veja o badge** aparecer no sino (canto superior direito)
5. **Clique no sino** para ver a notificação
6. **Teste as ações**:
   - Clique no botão de ação (se houver)
   - Marque como lida
   - Remova a notificação

## Console do Navegador

Abra o console (F12) e você verá logs como:

```
Buscando notificações...
Notificações atualizadas: 3 não lidas
```

## Simulando Processamento de Fila

### 1. Inicie o worker da fila

```bash
php artisan queue:work
```

### 2. Em outro terminal, dispare um job

```bash
php artisan tinker
```

```php
// Dispara um job de teste
dispatch(new \App\Jobs\ImportSupplierFile(1));
```

### 3. Adicione notificação no job

Edite o job para enviar notificação quando concluir.

## Testando Performance

### Simular Muitas Notificações

```php
use App\Helpers\NotificationHelper;

for ($i = 1; $i <= 50; $i++) {
    NotificationHelper::info(
        "Notificação #{$i}",
        "Esta é a notificação de teste número {$i}"
    );
}
```

### Verificar Carga no Servidor

```bash
# Ver queries executadas
tail -f storage/logs/laravel.log | grep "SELECT.*notifications"
```

## Troubleshooting

### ❌ Badge não aparece

**Solução**:
1. Verifique se criou a notificação com sucesso
2. Aguarde 10 segundos (intervalo de polling)
3. Abra o console (F12) e veja se há erros
4. Confirme que está logado

### ❌ Erro 404 ao buscar notificações

**Solução**:
1. Execute: `php artisan route:clear`
2. Verifique se as rotas estão em `routes/web.php`
3. Confirme que o middleware `auth` está aplicado

### ❌ Notificações não aparecem na lista

**Solução**:
1. Verifique se a migration foi executada: `php artisan migrate:status`
2. Confirme que a tabela `notifications` existe
3. Verifique se o user_id está correto (ou use null para global)

### ❌ Som não toca

**Solução**:
1. Verifique se `NOTIFICATION_SOUND_ENABLED` está `true`
2. Alguns navegadores bloqueiam som sem interação do usuário
3. Clique na página antes para permitir áudio

## Exemplo Completo de Integração

Aqui está um exemplo real de como usar em um controller:

```php
<?php

namespace App\Http\Controllers\Panel;

use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class ImportUIController extends Controller
{
    public function processProducts(Request $request, $id)
    {
        $import = SupplierImport::findOrFail($id);

        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return back()->with('error', 'Selecione ao menos um produto');
        }

        // Dispara job de processamento
        ProcessProductWithAI::dispatch($productIds);

        // Envia notificação imediata
        NotificationHelper::info(
            'Processamento Iniciado',
            count($productIds) . ' produto(s) foram enviados para processamento com IA.',
            route('panel.products.index'),
            'Ver Produtos'
        );

        return back()->with('ok', 'Produtos enviados para processamento!');
    }
}
```

## Script de Teste Rápido

Salve isto como `test-notifications.php` na raiz do projeto:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\NotificationHelper;

echo "Criando notificações de teste...\n";

NotificationHelper::success('Teste 1', 'Notificação de sucesso');
NotificationHelper::info('Teste 2', 'Notificação informativa');
NotificationHelper::warning('Teste 3', 'Notificação de aviso');
NotificationHelper::error('Teste 4', 'Notificação de erro');

echo "4 notificações criadas! Aguarde 10 segundos e verifique o painel.\n";
```

Execute com:
```bash
php test-notifications.php
```

---

## ✅ Checklist de Teste

- [ ] Criar notificação via Tinker
- [ ] Ver badge aparecer no sino
- [ ] Abrir dropdown de notificações
- [ ] Clicar em "Ver detalhes" (se houver link)
- [ ] Marcar notificação como lida
- [ ] Criar múltiplas notificações
- [ ] Testar "Marcar todas como lidas"
- [ ] Remover uma notificação
- [ ] Verificar som de notificação (se ativado)
- [ ] Testar com a aba inativa (polling deve pausar)
- [ ] Integrar em um job real
- [ ] Verificar performance com muitas notificações

Após completar todos os testes, o sistema estará validado e pronto para uso! 🚀
