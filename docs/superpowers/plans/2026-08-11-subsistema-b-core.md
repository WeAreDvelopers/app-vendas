# Subsistema B — Núcleo (B-core): Captura Real de Vendas do ML — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Capturar pedidos reais do Mercado Livre via webhook, atribuídos à empresa correta, persistidos de forma normalizada e idempotente (orders + order_items), com o lojista notificado — destravando o subsistema C.

**Architecture:** Webhook fino responde 200 e enfileira; um job resolve a empresa pelo `ml_user_id`, busca o pedido na API do ML e delega a um `OrderIngestionService` que faz upsert idempotente de `Order` + `OrderItem` escopados por empresa. Remove o código morto/DEMO do caminho de pedido.

**Tech Stack:** Laravel 12, PHP 8.2+, PHPUnit 11, SQLite in-memory (testes), fila `database` (prod)/`sync` (testes), `Illuminate\Support\Facades\Http` (+ `Http::fake` nos testes).

## Global Constraints

- PHP `^8.2`, Laravel `^12.0`.
- Multi-tenant por `company_id`; jobs rodam SEM `CurrentCompany` → sempre gravar `company_id` explícito; leituras de sistema via `withoutCompanyScope()` quando necessário (padrão do subsistema A).
- Isolamento: `Order`/`OrderItem`/`Supplier`/`Product` usam o trait `App\Models\Concerns\BelongsToCompany` (global scope por `<table>.company_id`).
- Fonte única "vendedor ML → empresa": `company_integrations.ml_user_id` (novo, indexado). `integration_type = 'mercado_livre'`.
- `orders.ml_order_id` é UNIQUE → idempotência por upsert nesse campo. `orders.payload` é NOT NULL → sempre preencher.
- Migrations MySQL-only (ex.: alterar enum) guardadas por driver (`DB::getDriverName() === 'mysql'`); no-op no SQLite dos testes.
- Testes: SQLite `:memory:` + `RefreshDatabase`; API do ML via `Http::fake`; fila via `Queue::fake`/`Bus::fake`. Sem chamadas de rede reais.
- Trabalhar na branch `feat/subsistema-b-vendas-ml` (já criada, inclui o subsistema A).
- Base ML API: `https://api.mercadolibre.com`.

---

## File Structure

**Criar:**
- `database/migrations/2026_08_11_000001_add_sales_fields_to_orders.php`
- `database/migrations/2026_08_11_000002_add_fields_to_order_items.php`
- `database/migrations/2026_08_11_000003_add_ml_user_id_to_company_integrations.php`
- `app/Models/OrderItem.php`
- `app/Support/MercadoLivre/CompanyResolver.php`
- `app/Services/OrderIngestionService.php`
- `app/Jobs/IngestMLOrder.php`
- `tests/Feature/*` conforme cada task.

**Modificar:**
- `app/Models/Order.php` — casts + `hasMany(OrderItem)`.
- `app/Services/MercadoLivreService.php` — método `getOrder`.
- `app/Http/Controllers/Api/WebhookController.php` — intake fino (validar + 200 + enfileirar); remover `saveOrder`/`processOrder` quebrados do caminho de pedido.
- `app/Http/Controllers/Panel/IntegrationController.php` — gravar `ml_user_id` no callback OAuth.

**Remover:**
- `app/Http/Controllers/WebhookController.php` (raiz, órfão).
- `app/Jobs/HandleMLWebhook.php` (DEMO).

---

### Task 1: Migration — campos de venda em `orders` + casts no model

**Files:**
- Create: `database/migrations/2026_08_11_000001_add_sales_fields_to_orders.php`
- Modify: `app/Models/Order.php`
- Test: `tests/Feature/OrderSalesFieldsTest.php`

**Interfaces:**
- Produces: colunas em `orders`: `total_amount`, `paid_amount` (decimal 12,2 nullable), `currency` (string 3 default 'BRL'), `buyer_ml_id` (string nullable, index), `buyer_nickname` (string nullable), `payment_status` (string nullable), `shipping_status` (string nullable), `shipment_id` (string nullable, index), `date_closed` (timestamp nullable, index). `orders.status` convertido para string livre. `Order` com casts.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OrderSalesFieldsTest.php
namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrderSalesFieldsTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_order_persists_normalized_sales_fields(): void
    {
        $a = $this->makeCompany('A');
        $this->setCurrentCompany($a);

        $order = Order::create([
            'company_id' => $a->id,
            'ml_order_id' => 'ML-1',
            'status' => 'paid',
            'payload' => json_encode(['id' => 'ML-1']),
            'total_amount' => 150.50,
            'paid_amount' => 150.50,
            'currency' => 'BRL',
            'buyer_ml_id' => 'B123',
            'buyer_nickname' => 'comprador',
            'payment_status' => 'approved',
            'shipping_status' => 'ready_to_ship',
            'shipment_id' => 'S999',
            'date_closed' => '2026-08-10 12:00:00',
        ]);

        $fresh = $order->fresh();
        $this->assertSame('150.50', (string) $fresh->total_amount);
        $this->assertSame('BRL', $fresh->currency);
        $this->assertSame('B123', $fresh->buyer_ml_id);
        $this->assertSame('S999', $fresh->shipment_id);
        $this->assertNotNull($fresh->date_closed);
        // status é string livre agora:
        $this->assertSame('paid', $fresh->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrderSalesFieldsTest`
Expected: FAIL — colunas inexistentes.

- [ ] **Step 3: Create the migration**

```php
<?php
// database/migrations/2026_08_11_000001_add_sales_fields_to_orders.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->nullable()->after('status');
            $table->decimal('paid_amount', 12, 2)->nullable()->after('total_amount');
            $table->string('currency', 3)->default('BRL')->after('paid_amount');
            $table->string('buyer_ml_id')->nullable()->index()->after('currency');
            $table->string('buyer_nickname')->nullable()->after('buyer_ml_id');
            $table->string('payment_status')->nullable()->after('buyer_nickname');
            $table->string('shipping_status')->nullable()->after('payment_status');
            $table->string('shipment_id')->nullable()->index()->after('shipping_status');
            $table->timestamp('date_closed')->nullable()->index()->after('shipment_id');
        });

        // orders.status: enum -> string livre (status do ML são muitos). MySQL-only DDL.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'ready_to_print'");
        }
        // SQLite: colunas enum já são varchar; nenhum ALTER necessário.
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'total_amount', 'paid_amount', 'currency', 'buyer_ml_id',
                'buyer_nickname', 'payment_status', 'shipping_status',
                'shipment_id', 'date_closed',
            ]);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('paid','shipped','canceled','ready_to_print') NOT NULL DEFAULT 'ready_to_print'");
        }
    }
};
```

- [ ] **Step 4: Add casts to the Order model**

Em `app/Models/Order.php`, adicionar (mantendo `use BelongsToCompany` e `$guarded = []`):

```php
protected $casts = [
    'total_amount' => 'decimal:2',
    'paid_amount' => 'decimal:2',
    'date_closed' => 'datetime',
    'payload' => 'array',
];
```

> Nota: `payload` cast para array permite passar array em `Order::create(['payload' => [...]])`. Onde o código antigo passar `json_encode(...)` string, o cast aceita string JSON também na leitura; para escrita, preferir array. No teste acima passamos `json_encode(...)` — com cast `array`, ao ler `$fresh->payload` vira array; o teste não lê payload, então é indiferente. Manter consistência: gravar array daqui pra frente.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=OrderSalesFieldsTest`
Expected: PASS.

- [ ] **Step 6: Full suite + commit**

Run: `php artisan test`
Expected: PASS (sem regressões).

```bash
git add database/migrations/2026_08_11_000001_add_sales_fields_to_orders.php app/Models/Order.php tests/Feature/OrderSalesFieldsTest.php
git commit -m "feat: campos normalizados de venda em orders + status string"
```

---

### Task 2: Migration — `order_items` (company_id, ml_item_id) + model `OrderItem`

**Files:**
- Create: `database/migrations/2026_08_11_000002_add_fields_to_order_items.php`
- Create: `app/Models/OrderItem.php`
- Modify: `app/Models/Order.php` (hasMany)
- Test: `tests/Feature/OrderItemScopeTest.php`

**Interfaces:**
- Consumes: trait `BelongsToCompany`; `Order`.
- Produces: `order_items.company_id` (FK companies nullable cascade), `order_items.ml_item_id` (string nullable index). Model `App\Models\OrderItem` (`$guarded=[]`, trait, `belongsTo(Order)`, `belongsTo(Product)`). `Order::items(): HasMany`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OrderItemScopeTest.php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrderItemScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_order_items_are_scoped_by_company_and_linked_to_order(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        $this->setCurrentCompany($a);
        $orderA = Order::create(['company_id' => $a->id, 'ml_order_id' => 'A1', 'status' => 'paid', 'payload' => []]);
        OrderItem::create(['company_id' => $a->id, 'order_id' => $orderA->id, 'ml_item_id' => 'MLB1', 'title' => 'Item A', 'qty' => 2, 'price' => 10.00]);

        $this->setCurrentCompany($b);
        $orderB = Order::create(['company_id' => $b->id, 'ml_order_id' => 'B1', 'status' => 'paid', 'payload' => []]);
        OrderItem::create(['company_id' => $b->id, 'order_id' => $orderB->id, 'ml_item_id' => 'MLB2', 'title' => 'Item B', 'qty' => 1, 'price' => 5.00]);

        $this->setCurrentCompany($a);
        $this->assertSame(['MLB1'], OrderItem::pluck('ml_item_id')->all());
        $this->assertCount(1, $orderA->fresh()->items);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrderItemScopeTest`
Expected: FAIL — `OrderItem` inexistente / coluna faltando.

- [ ] **Step 3: Create the migration**

```php
<?php
// database/migrations/2026_08_11_000002_add_fields_to_order_items.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->string('ml_item_id')->nullable()->index()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'ml_item_id']);
        });
    }
};
```

- [ ] **Step 4: Create the OrderItem model**

```php
<?php
// app/Models/OrderItem.php
namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use BelongsToCompany;

    protected $table = 'order_items';
    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 5: Add the relation to Order**

Em `app/Models/Order.php` adicionar:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function items(): HasMany
{
    return $this->hasMany(OrderItem::class);
}
```

- [ ] **Step 6: Run test + full suite + commit**

Run: `php artisan test --filter=OrderItemScopeTest` → PASS; depois `php artisan test` → PASS.

```bash
git add database/migrations/2026_08_11_000002_add_fields_to_order_items.php app/Models/OrderItem.php app/Models/Order.php tests/Feature/OrderItemScopeTest.php
git commit -m "feat: order_items escopado (company_id/ml_item_id) + model OrderItem"
```

---

### Task 3: Migration — `company_integrations.ml_user_id` + gravação no OAuth

**Files:**
- Create: `database/migrations/2026_08_11_000003_add_ml_user_id_to_company_integrations.php`
- Modify: `app/Http/Controllers/Panel/IntegrationController.php` (callback OAuth)
- Test: `tests/Feature/CompanyIntegrationMlUserIdTest.php`

**Interfaces:**
- Produces: `company_integrations.ml_user_id` (string nullable, index), preenchido no callback OAuth e por backfill do JSON `credentials.user_id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/CompanyIntegrationMlUserIdTest.php
namespace Tests\Feature;

use App\Models\CompanyIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyIntegrationMlUserIdTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_ml_user_id_column_exists_and_is_queryable(): void
    {
        $this->assertTrue(Schema::hasColumn('company_integrations', 'ml_user_id'));

        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '123456789',
            'credentials' => ['access_token' => 'x', 'user_id' => '123456789'],
        ]);

        $found = CompanyIntegration::where('integration_type', 'mercado_livre')
            ->where('ml_user_id', '123456789')->first();

        $this->assertNotNull($found);
        $this->assertSame($a->id, $found->company_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CompanyIntegrationMlUserIdTest`
Expected: FAIL — coluna `ml_user_id` inexistente. (Se `CompanyIntegration` não tiver `ml_user_id` no `$fillable`, adicioná-lo no Step 4.)

- [ ] **Step 3: Create the migration (+ backfill from JSON)**

```php
<?php
// database/migrations/2026_08_11_000003_add_ml_user_id_to_company_integrations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->string('ml_user_id')->nullable()->index()->after('integration_type');
        });

        // Backfill: extrai credentials.user_id do JSON para a nova coluna (ML apenas).
        foreach (DB::table('company_integrations')
            ->where('integration_type', 'mercado_livre')
            ->whereNull('ml_user_id')->get() as $row) {
            $creds = json_decode($row->credentials ?? '{}', true);
            $mlUserId = $creds['user_id'] ?? null;
            if ($mlUserId) {
                DB::table('company_integrations')->where('id', $row->id)
                    ->update(['ml_user_id' => (string) $mlUserId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->dropIndex(['ml_user_id']);
            $table->dropColumn('ml_user_id');
        });
    }
};
```

- [ ] **Step 4: Ensure the model is fillable + write ml_user_id at OAuth callback**

Em `app/Models/CompanyIntegration.php`, adicionar `'ml_user_id'` ao `$fillable`.

Em `app/Http/Controllers/Panel/IntegrationController.php`, no `mercadoLivreCallback` onde faz `CompanyIntegration::updateOrCreate([...], ['credentials' => [...'user_id' => $data['user_id']...], ...])`, incluir também no array de valores atualizados:

```php
'ml_user_id' => (string) ($data['user_id'] ?? ''),
```

(deixe o valor no JSON `credentials` como está, para compatibilidade). Se `$data['user_id']` puder ser vazio, gravar `null` em vez de string vazia.

- [ ] **Step 5: Run test + full suite + commit**

Run: `php artisan test --filter=CompanyIntegrationMlUserIdTest` → PASS; `php artisan test` → PASS.

```bash
git add database/migrations/2026_08_11_000003_add_ml_user_id_to_company_integrations.php app/Models/CompanyIntegration.php app/Http/Controllers/Panel/IntegrationController.php tests/Feature/CompanyIntegrationMlUserIdTest.php
git commit -m "feat: ml_user_id consultavel em company_integrations (fonte unica vendedor->empresa)"
```

---

### Task 4: `CompanyResolver::companyIdForMlUser`

**Files:**
- Create: `app/Support/MercadoLivre/CompanyResolver.php`
- Test: `tests/Feature/CompanyResolverTest.php`

**Interfaces:**
- Consumes: `company_integrations.ml_user_id` (Task 3).
- Produces: `App\Support\MercadoLivre\CompanyResolver::companyIdForMlUser(int|string $mlUserId): ?int` — retorna o `company_id` da integração ML ativa com aquele `ml_user_id`, ou `null`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/CompanyResolverTest.php
namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Support\MercadoLivre\CompanyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyResolverTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_resolves_company_for_known_ml_user(): void
    {
        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id, 'integration_type' => 'mercado_livre',
            'active' => true, 'ml_user_id' => '777', 'credentials' => ['user_id' => '777'],
        ]);

        $resolver = new CompanyResolver();
        $this->assertSame($a->id, $resolver->companyIdForMlUser('777'));
        $this->assertSame($a->id, $resolver->companyIdForMlUser(777));
    }

    public function test_returns_null_for_unknown_ml_user(): void
    {
        $resolver = new CompanyResolver();
        $this->assertNull($resolver->companyIdForMlUser('does-not-exist'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CompanyResolverTest`
Expected: FAIL — classe inexistente.

- [ ] **Step 3: Create the resolver**

```php
<?php
// app/Support/MercadoLivre/CompanyResolver.php
namespace App\Support\MercadoLivre;

use App\Models\CompanyIntegration;

class CompanyResolver
{
    public function companyIdForMlUser(int|string $mlUserId): ?int
    {
        $integration = CompanyIntegration::query()
            ->where('integration_type', 'mercado_livre')
            ->where('ml_user_id', (string) $mlUserId)
            ->first();

        return $integration?->company_id;
    }
}
```

> Nota: `CompanyIntegration` NÃO usa `BelongsToCompany` (é a própria tabela de integração; consultada por `ml_user_id` sem contexto de empresa), então a query não é filtrada por `CurrentCompany` — correto para o worker do webhook.

- [ ] **Step 4: Run test + commit**

Run: `php artisan test --filter=CompanyResolverTest` → PASS.

```bash
git add app/Support/MercadoLivre/CompanyResolver.php tests/Feature/CompanyResolverTest.php
git commit -m "feat: CompanyResolver ml_user_id -> company_id"
```

---

### Task 5: `MercadoLivreService::getOrder`

**Files:**
- Modify: `app/Services/MercadoLivreService.php`
- Test: `tests/Feature/MercadoLivreGetOrderTest.php`

**Interfaces:**
- Consumes: `getActiveTokenFromIntegration(int $companyId): ?object` (já existe — retorna objeto com `access_token`).
- Produces: `MercadoLivreService::getOrder(int $companyId, string $orderId): ?array` — GET `/orders/{id}`; retorna o array do pedido ou `null` em falha/sem token.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/MercadoLivreGetOrderTest.php
namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Services\MercadoLivreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class MercadoLivreGetOrderTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_fetches_order_from_ml_api(): void
    {
        $a = $this->makeCompany('A');
        // Integração com token válido (expira no futuro) para evitar refresh.
        CompanyIntegration::create([
            'company_id' => $a->id, 'integration_type' => 'mercado_livre', 'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'valid-token', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'api.mercadolibre.com/orders/ML-1' => Http::response(['id' => 'ML-1', 'total_amount' => 99.9], 200),
        ]);

        $svc = app(MercadoLivreService::class);
        $order = $svc->getOrder($a->id, 'ML-1');

        $this->assertIsArray($order);
        $this->assertSame('ML-1', $order['id']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/orders/ML-1')
            && $req->hasHeader('Authorization', 'Bearer valid-token'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MercadoLivreGetOrderTest`
Expected: FAIL — método `getOrder` inexistente.

> Nota: se `getActiveTokenFromIntegration` disparar refresh quando `expires_at` estiver perto/nulo, o teste seta `expires_at` no futuro para evitar chamada de rede de refresh. Se ainda assim ocorrer, ajustar o fixture (ex.: setar `credentials.expires_at`/`connected_at`) conforme a lógica real de `getActiveTokenFromIntegration` — leia o método antes de implementar.

- [ ] **Step 3: Implement getOrder**

Adicionar em `app/Services/MercadoLivreService.php` (usa o mesmo padrão `Http::withToken(...)->get(...)` já presente no service):

```php
public function getOrder(int $companyId, string $orderId): ?array
{
    $token = $this->getActiveTokenFromIntegration($companyId);
    if (!$token || empty($token->access_token)) {
        \Log::warning('getOrder: sem token para empresa', ['company_id' => $companyId]);
        return null;
    }

    $response = \Illuminate\Support\Facades\Http::withToken($token->access_token)
        ->get("https://api.mercadolibre.com/orders/{$orderId}");

    if (!$response->successful()) {
        \Log::error('getOrder: falha ao buscar pedido', [
            'order_id' => $orderId, 'status' => $response->status(),
        ]);
        return null;
    }

    return $response->json();
}
```

- [ ] **Step 4: Run test + commit**

Run: `php artisan test --filter=MercadoLivreGetOrderTest` → PASS.

```bash
git add app/Services/MercadoLivreService.php tests/Feature/MercadoLivreGetOrderTest.php
git commit -m "feat: MercadoLivreService::getOrder (GET /orders/{id})"
```

---

### Task 6: `OrderIngestionService::ingest` (núcleo — upsert idempotente)

**Files:**
- Create: `app/Services/OrderIngestionService.php`
- Test: `tests/Feature/OrderIngestionServiceTest.php`

**Interfaces:**
- Consumes: `Order`, `OrderItem`, `Product` (por SKU), `App\Helpers\NotificationHelper` (para notificar; se a assinatura diferir, ver Step 3).
- Produces: `OrderIngestionService::ingest(int $companyId, array $mlOrder): \App\Models\Order` — upsert idempotente de `Order` (por `company_id`+`ml_order_id`) e seus `OrderItem` (por `order_id`+`ml_item_id`), a partir do payload do ML. `company_id` sempre explícito.

**Mapa de campos (payload ML `/orders/{id}` → colunas):**
- `ml_order_id` ← `$mlOrder['id']`
- `status` ← `$mlOrder['status']`
- `total_amount` ← `$mlOrder['total_amount']`
- `paid_amount` ← `$mlOrder['paid_amount']`
- `currency` ← `$mlOrder['currency_id']` (default 'BRL')
- `buyer_ml_id` ← `$mlOrder['buyer']['id']`; `buyer_nickname` ← `$mlOrder['buyer']['nickname']`
- `payment_status` ← `$mlOrder['payments'][0]['status']`
- `shipping_status` ← `$mlOrder['shipping']['status']`; `shipment_id` ← `$mlOrder['shipping']['id']`
- `date_closed` ← `$mlOrder['date_closed']`
- `payload` ← `$mlOrder` (array completo)
- itens: `$mlOrder['order_items'][]` → `ml_item_id` ← `item['item']['id']`; `title` ← `item['item']['title']`; `qty` ← `item['quantity']`; `price` ← `item['unit_price']`; SKU ← `item['item']['seller_sku']` ou `item['item']['seller_custom_field']`

- [ ] **Step 1: Write the failing test (idempotência + mapeamento + isolamento)**

```php
<?php
// tests/Feature/OrderIngestionServiceTest.php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrderIngestionServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function samplePayload(): array
    {
        return [
            'id' => 'ML-100',
            'status' => 'paid',
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'currency_id' => 'BRL',
            'date_closed' => '2026-08-10T12:00:00.000-03:00',
            'buyer' => ['id' => 555, 'nickname' => 'joao'],
            'payments' => [['status' => 'approved']],
            'shipping' => ['id' => 'SHP-9', 'status' => 'ready_to_ship'],
            'order_items' => [
                ['item' => ['id' => 'MLB111', 'title' => 'Camiseta', 'seller_sku' => 'SKU-1'], 'quantity' => 2, 'unit_price' => 50.00],
                ['item' => ['id' => 'MLB222', 'title' => 'Boné', 'seller_sku' => 'SKU-2'], 'quantity' => 2, 'unit_price' => 50.00],
            ],
        ];
    }

    public function test_ingests_order_with_items_and_is_idempotent(): void
    {
        $a = $this->makeCompany('A');
        // Produto local para provar o link por SKU:
        $this->setCurrentCompany($a);
        Product::create(['sku' => 'SKU-1', 'name' => 'Camiseta']);

        $svc = app(OrderIngestionService::class);
        $order = $svc->ingest($a->id, $this->samplePayload());

        $this->assertSame('ML-100', $order->ml_order_id);
        $this->assertSame($a->id, $order->company_id);
        $this->assertSame('200.00', (string) $order->total_amount);
        $this->assertSame('approved', $order->payment_status);
        $this->assertSame('SHP-9', $order->shipment_id);
        $this->assertCount(2, $order->items);

        // Item com SKU conhecido linka ao Product; item sem SKU conhecido fica product_id null.
        $linked = OrderItem::where('ml_item_id', 'MLB111')->first();
        $this->assertNotNull($linked->product_id);
        $unlinked = OrderItem::where('ml_item_id', 'MLB222')->first();
        $this->assertNull($unlinked->product_id);

        // Idempotência: reingerir o MESMO pedido não duplica.
        $svc->ingest($a->id, $this->samplePayload());
        $this->assertSame(1, Order::where('ml_order_id', 'ML-100')->count());
        $this->assertSame(2, OrderItem::where('order_id', $order->id)->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrderIngestionServiceTest`
Expected: FAIL — service inexistente.

- [ ] **Step 3: Implement the service**

```php
<?php
// app/Services/OrderIngestionService.php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderIngestionService
{
    public function ingest(int $companyId, array $mlOrder): Order
    {
        $mlOrderId = (string) ($mlOrder['id'] ?? '');

        return DB::transaction(function () use ($companyId, $mlOrder, $mlOrderId) {
            $order = Order::withoutCompanyScope()
                ->firstOrNew(['company_id' => $companyId, 'ml_order_id' => $mlOrderId]);

            $order->company_id = $companyId;
            $order->ml_order_id = $mlOrderId;
            $order->status = (string) ($mlOrder['status'] ?? 'unknown');
            $order->total_amount = $mlOrder['total_amount'] ?? null;
            $order->paid_amount = $mlOrder['paid_amount'] ?? null;
            $order->currency = $mlOrder['currency_id'] ?? 'BRL';
            $order->buyer_ml_id = isset($mlOrder['buyer']['id']) ? (string) $mlOrder['buyer']['id'] : null;
            $order->buyer_nickname = $mlOrder['buyer']['nickname'] ?? null;
            $order->payment_status = $mlOrder['payments'][0]['status'] ?? null;
            $order->shipping_status = $mlOrder['shipping']['status'] ?? null;
            $order->shipment_id = isset($mlOrder['shipping']['id']) ? (string) $mlOrder['shipping']['id'] : null;
            $order->date_closed = $mlOrder['date_closed'] ?? null;
            $order->payload = $mlOrder;
            $order->save();

            foreach (($mlOrder['order_items'] ?? []) as $line) {
                $item = $line['item'] ?? [];
                $mlItemId = isset($item['id']) ? (string) $item['id'] : null;
                $sku = $item['seller_sku'] ?? $item['seller_custom_field'] ?? null;

                $productId = null;
                if ($sku) {
                    $productId = Product::withoutCompanyScope()
                        ->where('company_id', $companyId)->where('sku', $sku)->value('id');
                }

                $oi = OrderItem::withoutCompanyScope()->firstOrNew([
                    'order_id' => $order->id,
                    'ml_item_id' => $mlItemId,
                ]);
                $oi->company_id = $companyId;
                $oi->order_id = $order->id;
                $oi->ml_item_id = $mlItemId;
                $oi->product_id = $productId;
                $oi->title = $item['title'] ?? '';
                $oi->qty = (int) ($line['quantity'] ?? 1);
                $oi->price = $line['unit_price'] ?? 0;
                $oi->save();
            }

            $this->notify($companyId, $order);

            return $order;
        });
    }

    private function notify(int $companyId, Order $order): void
    {
        // `notifications` é keyed por user_id (não tem company_id) e o worker não
        // tem usuário logado → notificar cada usuário da empresa.
        // NotificationHelper::success(title, message, actionUrl, actionText, userId).
        try {
            $company = \App\Models\Company::find($companyId);
            if (!$company) {
                return;
            }
            $message = "Pedido {$order->ml_order_id} — R$ " . number_format((float) $order->total_amount, 2, ',', '.');
            foreach ($company->users()->pluck('users.id') as $userId) {
                \App\Helpers\NotificationHelper::success(
                    'Nova venda no Mercado Livre',
                    $message,
                    '/panel/orders',
                    'Ver pedidos',
                    (int) $userId,
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao notificar nova venda', ['error' => $e->getMessage()]);
        }
    }
}
```

> Nota: `Order`/`OrderItem`/`Product` são escopados; o service roda no worker sem `CurrentCompany`, então TODAS as queries usam `withoutCompanyScope()` + `company_id` explícito. `Company` NÃO é escopado (tenant root), então `Company::find`/`$company->users()` funcionam sem contexto. A notificação está em try/catch para nunca quebrar a ingestão. Confirme que `Company::users()` existe (belongsToMany via pivot `company_user`) — existe no subsistema A.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OrderIngestionServiceTest`
Expected: PASS.

- [ ] **Step 5: Full suite + commit**

Run: `php artisan test` → PASS.

```bash
git add app/Services/OrderIngestionService.php tests/Feature/OrderIngestionServiceTest.php
git commit -m "feat: OrderIngestionService (upsert idempotente order+items escopado)"
```

---

### Task 7: Job `IngestMLOrder`

**Files:**
- Create: `app/Jobs/IngestMLOrder.php`
- Test: `tests/Feature/IngestMLOrderJobTest.php`

**Interfaces:**
- Consumes: `MercadoLivreService::getOrder`, `OrderIngestionService::ingest`.
- Produces: `App\Jobs\IngestMLOrder(int $companyId, string $orderId)` (`ShouldQueue`); no `handle()` busca o pedido e ingere. Se `getOrder` retornar null, loga e retorna (sem lançar, para não reenfileirar infinitamente por pedido inexistente).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/IngestMLOrderJobTest.php
namespace Tests\Feature;

use App\Jobs\IngestMLOrder;
use App\Models\CompanyIntegration;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class IngestMLOrderJobTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_job_fetches_and_ingests_order(): void
    {
        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id, 'integration_type' => 'mercado_livre', 'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'valid-token', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'api.mercadolibre.com/orders/ML-1' => Http::response([
                'id' => 'ML-1', 'status' => 'paid', 'total_amount' => 10.0,
                'currency_id' => 'BRL', 'order_items' => [],
            ], 200),
        ]);

        (new IngestMLOrder($a->id, 'ML-1'))->handle(
            app(\App\Services\MercadoLivreService::class),
            app(\App\Services\OrderIngestionService::class),
        );

        $this->assertDatabaseHas('orders', ['ml_order_id' => 'ML-1', 'company_id' => $a->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=IngestMLOrderJobTest`
Expected: FAIL — job inexistente.

- [ ] **Step 3: Implement the job**

```php
<?php
// app/Jobs/IngestMLOrder.php
namespace App\Jobs;

use App\Services\MercadoLivreService;
use App\Services\OrderIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestMLOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(public int $companyId, public string $orderId) {}

    public function handle(MercadoLivreService $ml, OrderIngestionService $ingestion): void
    {
        $mlOrder = $ml->getOrder($this->companyId, $this->orderId);
        if (!$mlOrder) {
            \Log::warning('IngestMLOrder: pedido não obtido', [
                'company_id' => $this->companyId, 'order_id' => $this->orderId,
            ]);
            return;
        }
        $ingestion->ingest($this->companyId, $mlOrder);
    }
}
```

- [ ] **Step 4: Run test + commit**

Run: `php artisan test --filter=IngestMLOrderJobTest` → PASS.

```bash
git add app/Jobs/IngestMLOrder.php tests/Feature/IngestMLOrderJobTest.php
git commit -m "feat: job IngestMLOrder (fetch + ingest)"
```

---

### Task 8: Webhook fino — validar, responder 200, enfileirar

**Files:**
- Modify: `app/Http/Controllers/Api/WebhookController.php`
- Test: `tests/Feature/WebhookIntakeTest.php`

**Interfaces:**
- Consumes: `CompanyResolver::companyIdForMlUser`, `IngestMLOrder`.
- Produces: `POST /api/webhooks/mercado-livre` valida `topic`/`resource`/`user_id`, responde 200 e, para `orders_v2`, enfileira `IngestMLOrder(companyId, orderId)` quando a empresa é resolvida. `user_id` desconhecido → 200 + log (descartado). Outros tópicos (`items`/`questions`/`claims`) → 200 + log (implementação completa no plano B-topics). NUNCA chama a API do ML na thread do request.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/WebhookIntakeTest.php
namespace Tests\Feature;

use App\Jobs\IngestMLOrder;
use App\Models\CompanyIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class WebhookIntakeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_orders_v2_notification_enqueues_ingestion_for_resolved_company(): void
    {
        Queue::fake();
        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id, 'integration_type' => 'mercado_livre',
            'active' => true, 'ml_user_id' => '777', 'credentials' => ['user_id' => '777'],
        ]);

        $this->postJson('/api/webhooks/mercado-livre', [
            'topic' => 'orders_v2',
            'resource' => '/orders/ML-1',
            'user_id' => 777,
        ])->assertOk();

        Queue::assertPushed(IngestMLOrder::class, fn ($job) =>
            $job->companyId === $a->id && $job->orderId === 'ML-1');
    }

    public function test_unknown_seller_is_accepted_but_not_enqueued(): void
    {
        Queue::fake();
        $this->postJson('/api/webhooks/mercado-livre', [
            'topic' => 'orders_v2', 'resource' => '/orders/ML-9', 'user_id' => 999999,
        ])->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_missing_fields_returns_400(): void
    {
        $this->postJson('/api/webhooks/mercado-livre', ['topic' => 'orders_v2'])
            ->assertStatus(400);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=WebhookIntakeTest`
Expected: FAIL — o controller atual não enfileira `IngestMLOrder` (usa `processOrder`/`saveOrder`).

- [ ] **Step 3: Rewrite the controller intake**

Substituir o corpo de `Api\WebhookController` por um intake fino. Manter a assinatura `mercadoLivre(Request $request)`. Remover `processOrder`, `processItem`, `processQuestion`, `processClaim`, `saveOrder`, `createUserNotification` (o caminho de pedido agora é o job + service). Manter `shopee`/`shopify` como estão (stubs).

```php
public function mercadoLivre(\Illuminate\Http\Request $request, \App\Support\MercadoLivre\CompanyResolver $resolver)
{
    $topic = $request->input('topic');
    $resource = $request->input('resource');
    $userId = $request->input('user_id');

    if (!$topic || !$resource || !$userId) {
        \Log::warning('ML Webhook missing required fields', $request->all());
        return response()->json(['message' => 'Missing required fields'], 400);
    }

    $companyId = $resolver->companyIdForMlUser($userId);
    if (!$companyId) {
        \Log::info('ML Webhook: vendedor desconhecido, descartado', ['user_id' => $userId]);
        return response()->json(['message' => 'ok'], 200);
    }

    if ($topic === 'orders_v2') {
        if (preg_match('/\/orders\/(\S+)/', $resource, $m)) {
            \App\Jobs\IngestMLOrder::dispatch($companyId, $m[1]);
        }
    } else {
        // items/questions/claims: implementados no plano B-topics.
        \Log::info('ML Webhook topic recebido (pendente B-topics)', ['topic' => $topic, 'company_id' => $companyId]);
    }

    return response()->json(['message' => 'ok'], 200);
}
```

Remover o `use App\Services\MercadoLivreService;` e o construtor que injeta o service, se não forem mais usados; remover imports não usados (`Http`, `DB`) do controller.

- [ ] **Step 4: Run test + full suite + commit**

Run: `php artisan test --filter=WebhookIntakeTest` → PASS; `php artisan test` → PASS.

```bash
git add app/Http/Controllers/Api/WebhookController.php tests/Feature/WebhookIntakeTest.php
git commit -m "fix: webhook ML fino (valida + 200 + enfileira IngestMLOrder); remove saveOrder quebrado"
```

---

### Task 9: Remover código morto/DEMO do caminho de pedido

**Files:**
- Remove: `app/Http/Controllers/WebhookController.php`, `app/Jobs/HandleMLWebhook.php`
- Test: `tests/Feature/DeadCodeRemovedTest.php`

**Interfaces:**
- Produces: repositório sem o controller raiz órfão nem o job DEMO. Nenhuma rota/refererência quebrada.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/DeadCodeRemovedTest.php
namespace Tests\Feature;

use Tests\TestCase;

class DeadCodeRemovedTest extends TestCase
{
    public function test_demo_order_classes_are_gone(): void
    {
        $this->assertFalse(class_exists(\App\Jobs\HandleMLWebhook::class));
        $this->assertFalse(class_exists(\App\Http\Controllers\WebhookController::class));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DeadCodeRemovedTest`
Expected: FAIL — classes ainda existem.

- [ ] **Step 3: Confirm nothing references them, then remove**

Verificar que nenhuma rota referencia o `WebhookController` raiz (o mapeamento é só `Api\WebhookController`) e que nada despacha `HandleMLWebhook`:

Run: `git grep -n "HandleMLWebhook\|Controllers\\\\WebhookController"` — esperado: apenas os próprios arquivos a remover (e docs). Se houver referência viva, ajustá-la antes.

```bash
git rm app/Http/Controllers/WebhookController.php app/Jobs/HandleMLWebhook.php
composer dump-autoload
```

- [ ] **Step 4: Run test + full suite + commit**

Run: `php artisan test --filter=DeadCodeRemovedTest` → PASS; `php artisan test` → PASS.

```bash
git add -A
git commit -m "chore: remover WebhookController raiz orfao e HandleMLWebhook demo"
```

---

## Notas finais de execução

- Ler `App\Helpers\NotificationHelper` e `MercadoLivreService::getActiveTokenFromIntegration` antes das Tasks 6 e 5, respectivamente — os fixtures/chamadas dependem das assinaturas reais.
- `PrintJob` NÃO é tocado neste plano (impressão real = plano B-printing). O caminho de pedido deixa de disparar `PrintJob` demo ao remover `HandleMLWebhook`.
- Este plano cobre B-core (B1+B2+B3 + limpeza). Planos seguintes: **B-backfill** (B4), **B-topics** (B5: items/questions/claims), **B-printing** (B6).
