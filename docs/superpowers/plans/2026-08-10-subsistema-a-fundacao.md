# Subsistema A — Fundação / Correções Críticas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Blindar a base da aplicação com isolamento multi-tenant automático, consolidar o pipeline de importação e corrigir riscos de segurança/rotas antes de construir novas features.

**Architecture:** Introduzir um resolver de empresa atual (`CurrentCompany`, singleton) alimentado pelo middleware; um trait `BelongsToCompany` que adiciona um Global Scope Eloquent filtrando por `company_id` e preenche `company_id` no `creating`; migrar as queries `DB::table()` dos caminhos de tenant para Eloquent para que o escopo se aplique; remover o pipeline de importação quebrado (auto-publicação) e o endpoint sem autenticação.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11, SQLite in-memory (testes), fila `database` (produção) / `sync` (testes).

## Global Constraints

- PHP `^8.2`, Laravel `^12.0` (de `composer.json`).
- Multi-tenant single-database por coluna `company_id`. Empresa ativa em `users.current_company_id`.
- Tabelas que já têm `company_id`: `products`, `supplier_imports`, `suppliers`, `listings`, `orders`.
- Tabelas-filho SEM `company_id` hoje: `products_raw`, `import_errors`, `product_images`, `product_integrations`, `mercado_livre_listings`, `order_items`.
- `DB::table()` ignora Global Scopes — todo caminho de tenant deve usar Eloquent.
- Testes: SQLite `:memory:` e `QUEUE_CONNECTION=sync` já configurados em `phpunit.xml`. Usar `RefreshDatabase`.
- Após importar, NÃO publicar automaticamente. Fluxo: importar → revisar → `ProcessProductWithAI` (manual) → publicar (manual).
- Trabalhar na branch `feat/subsistema-a-fundacao` (já criada).

---

## File Structure

**Criar:**
- `app/Support/CurrentCompany.php` — resolver singleton da empresa ativa.
- `app/Models/Scopes/CompanyScope.php` — Global Scope que filtra por `company_id`.
- `app/Models/Concerns/BelongsToCompany.php` — trait (scope + auto-fill + escape).
- `app/Models/Order.php` — model Eloquent fino para `orders`.
- `app/Models/Listing.php` — model Eloquent fino para `listings`.
- `database/migrations/2026_08_10_000001_add_company_id_to_child_tables.php` — `company_id` em `products_raw` e `import_errors` + backfill.
- `tests/Feature/*` e `tests/Unit/*` — conforme cada task.
- `tests/Concerns/CreatesTenants.php` — helper de teste (empresa/usuário/contexto).

**Modificar:**
- `app/Providers/AppServiceProvider.php` — bind singleton `CurrentCompany`.
- `app/Http/Middleware/EnsureUserHasCompany.php` — alimentar `CurrentCompany`; corrigir redirect de onboarding.
- `app/Models/{Supplier,Product,SupplierImport,ProductRaw,ImportError}.php` — aplicar trait.
- `app/Http/Controllers/Panel/{ImportUIController,OrderUIController,ListingUIController}.php` — Eloquent escopado.
- `app/Jobs/ImportSupplierFile.php` — remover auto-dispatch; gravar via Eloquent com `company_id`.
- `routes/web.php` — remover `/import/supplier`; mover `convert-without-ai` para `panel`/`auth`.
- `.gitignore` — padrões de higiene.

**Remover:**
- `app/Jobs/EnrichProduct.php`.
- `app/Http/Controllers/ImportController.php` (mover `convertWithoutAI` para `ImportUIController`).
- Arquivos soltos: `test_*.php`, `check_*.php`, `debug_attributes.php`, `nul`, `temp_original.txt`, `php_errors.log`.

---

### Task 1: Resolver `CurrentCompany` (singleton)

**Files:**
- Create: `app/Support/CurrentCompany.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/CurrentCompanyTest.php`

**Interfaces:**
- Produces: `App\Support\CurrentCompany` com `set(?int $companyId): void`, `id(): ?int`, `clear(): void`. Resolvível via `app(CurrentCompany::class)` (singleton por request).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/CurrentCompanyTest.php
namespace Tests\Unit;

use App\Support\CurrentCompany;
use Tests\TestCase;

class CurrentCompanyTest extends TestCase
{
    public function test_stores_and_returns_the_active_company_id(): void
    {
        $current = new CurrentCompany();
        $this->assertNull($current->id());

        $current->set(42);
        $this->assertSame(42, $current->id());

        $current->clear();
        $this->assertNull($current->id());
    }

    public function test_is_bound_as_a_singleton(): void
    {
        $a = app(CurrentCompany::class);
        $b = app(CurrentCompany::class);
        $this->assertSame($a, $b);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CurrentCompanyTest`
Expected: FAIL — classe `App\Support\CurrentCompany` não existe.

- [ ] **Step 3: Create the resolver**

```php
<?php
// app/Support/CurrentCompany.php
namespace App\Support;

class CurrentCompany
{
    protected ?int $id = null;

    public function set(?int $companyId): void
    {
        $this->id = $companyId;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clear(): void
    {
        $this->id = null;
    }
}
```

- [ ] **Step 4: Bind as singleton**

Em `app/Providers/AppServiceProvider.php`, dentro de `register()`, substituir o `//` por:

```php
$this->app->singleton(\App\Support\CurrentCompany::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=CurrentCompanyTest`
Expected: PASS (2 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Support/CurrentCompany.php app/Providers/AppServiceProvider.php tests/Unit/CurrentCompanyTest.php
git commit -m "feat: resolver CurrentCompany como singleton"
```

---

### Task 2: Trait `BelongsToCompany` + `CompanyScope` (validado no model `Supplier`)

**Files:**
- Create: `app/Models/Scopes/CompanyScope.php`
- Create: `app/Models/Concerns/BelongsToCompany.php`
- Create: `tests/Concerns/CreatesTenants.php`
- Modify: `app/Models/Supplier.php`
- Test: `tests/Feature/CompanyScopeTest.php`

**Interfaces:**
- Consumes: `App\Support\CurrentCompany` (Task 1).
- Produces:
  - `App\Models\Scopes\CompanyScope` (implements `Illuminate\Database\Eloquent\Scope`), filtra `where("<table>.company_id", <id atual>)` quando há empresa ativa; não filtra quando `CurrentCompany::id()` é `null`.
  - `App\Models\Concerns\BelongsToCompany` (trait): adiciona `CompanyScope`; no `creating` preenche `company_id` com a empresa atual se vazio; expõe `scopeWithoutCompanyScope($query)`; relação `company()`.
  - `Tests\Concerns\CreatesTenants` (trait de teste): `makeCompany(string $name = 'Acme'): Company`, `actingAsCompanyUser(Company $company): User`, `setCurrentCompany(Company $company): void`.

- [ ] **Step 1: Write the test helper trait**

```php
<?php
// tests/Concerns/CreatesTenants.php
namespace Tests\Concerns;

use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;

trait CreatesTenants
{
    protected function makeCompany(string $name = 'Acme'): Company
    {
        // company_id não se aplica a Company; criação direta.
        return Company::create(['name' => $name]);
    }

    protected function setCurrentCompany(Company $company): void
    {
        app(CurrentCompany::class)->set($company->id);
    }

    protected function actingAsCompanyUser(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $user->companies()->attach($company->id, ['is_admin' => true]);
        $this->actingAs($user);
        $this->setCurrentCompany($company);
        return $user;
    }
}
```

> Nota: se `Company::create` reclamar de mass-assignment, confirmar que `Company` usa `$guarded = []` ou incluir `name` no `$fillable`. Ajustar o model `Company` se necessário (adicionar `protected $guarded = [];`).

- [ ] **Step 2: Write the failing test**

```php
<?php
// tests/Feature/CompanyScopeTest.php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\Supplier;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_queries_only_return_current_company_records(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        Supplier::create(['name' => 'Fornecedor A', 'company_id' => $a->id, 'active' => true]);
        Supplier::create(['name' => 'Fornecedor B', 'company_id' => $b->id, 'active' => true]);

        $this->setCurrentCompany($a);
        $names = Supplier::pluck('name')->all();

        $this->assertSame(['Fornecedor A'], $names);
    }

    public function test_company_id_is_auto_filled_on_create(): void
    {
        $a = $this->makeCompany('A');
        $this->setCurrentCompany($a);

        $s = Supplier::create(['name' => 'Novo', 'active' => true]);

        $this->assertSame($a->id, $s->company_id);
    }

    public function test_without_company_scope_returns_all_records(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        Supplier::create(['name' => 'Fornecedor A', 'company_id' => $a->id, 'active' => true]);
        Supplier::create(['name' => 'Fornecedor B', 'company_id' => $b->id, 'active' => true]);

        $this->setCurrentCompany($a);

        $this->assertCount(2, Supplier::withoutCompanyScope()->get());
    }

    public function test_no_filter_when_no_current_company(): void
    {
        $a = $this->makeCompany('A');
        Supplier::create(['name' => 'X', 'company_id' => $a->id, 'active' => true]);

        app(CurrentCompany::class)->clear();

        $this->assertCount(1, Supplier::all());
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=CompanyScopeTest`
Expected: FAIL — `CompanyScope`/trait inexistentes e `withoutCompanyScope` indefinido.

- [ ] **Step 4: Create the scope**

```php
<?php
// app/Models/Scopes/CompanyScope.php
namespace App\Models\Scopes;

use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CurrentCompany::class)->id();

        if ($companyId !== null) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
```

- [ ] **Step 5: Create the trait**

```php
<?php
// app/Models/Concerns/BelongsToCompany.php
namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $companyId = app(CurrentCompany::class)->id();
                if ($companyId !== null) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
```

- [ ] **Step 6: Apply the trait to `Supplier`**

Em `app/Models/Supplier.php`, adicionar o `use` no topo da classe:

```php
use App\Models\Concerns\BelongsToCompany;
// dentro da classe:
class Supplier extends Model
{
    use BelongsToCompany;
    // ... resto inalterado
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=CompanyScopeTest`
Expected: PASS (4 testes).

- [ ] **Step 8: Commit**

```bash
git add app/Models/Scopes/CompanyScope.php app/Models/Concerns/BelongsToCompany.php app/Models/Supplier.php tests/Concerns/CreatesTenants.php tests/Feature/CompanyScopeTest.php
git commit -m "feat: trait BelongsToCompany + CompanyScope (global scope multi-tenant)"
```

---

### Task 3: `company_id` nas tabelas-filho + trait nos models de catálogo

**Files:**
- Create: `database/migrations/2026_08_10_000001_add_company_id_to_child_tables.php`
- Modify: `app/Models/Product.php`, `app/Models/SupplierImport.php`, `app/Models/ProductRaw.php`, `app/Models/ImportError.php`
- Test: `tests/Feature/ChildTableScopeTest.php`

**Interfaces:**
- Consumes: `BelongsToCompany` (Task 2).
- Produces: colunas `products_raw.company_id` e `import_errors.company_id` (nullable, FK `companies`, `onDelete cascade`), com backfill a partir do `supplier_import` correspondente. Models `Product`, `SupplierImport`, `ProductRaw`, `ImportError` passam a usar o trait.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ChildTableScopeTest.php
namespace Tests\Feature;

use App\Models\ProductRaw;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ChildTableScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_products_raw_is_scoped_by_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        $impA = SupplierImport::create(['company_id' => $a->id, 'supplier_name' => 'A', 'source_file' => 'a.csv', 'source_type' => 'csv', 'status' => 'queued']);
        $impB = SupplierImport::create(['company_id' => $b->id, 'supplier_name' => 'B', 'source_file' => 'b.csv', 'source_type' => 'csv', 'status' => 'queued']);

        ProductRaw::create(['company_id' => $a->id, 'supplier_import_id' => $impA->id, 'sku' => 'SKU-A', 'name' => 'A', 'status' => 'raw']);
        ProductRaw::create(['company_id' => $b->id, 'supplier_import_id' => $impB->id, 'sku' => 'SKU-B', 'name' => 'B', 'status' => 'raw']);

        $this->setCurrentCompany($a);

        $this->assertSame(['SKU-A'], ProductRaw::pluck('sku')->all());
        $this->assertSame(['A'], SupplierImport::pluck('supplier_name')->all());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChildTableScopeTest`
Expected: FAIL — coluna `products_raw.company_id` não existe / models sem escopo.

- [ ] **Step 3: Create the migration (add columns + backfill)**

```php
<?php
// database/migrations/2026_08_10_000001_add_company_id_to_child_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products_raw', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
        Schema::table('import_errors', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Backfill a partir do supplier_import correspondente (ignora órfãos).
        DB::statement('
            UPDATE products_raw
            SET company_id = (
                SELECT si.company_id FROM supplier_imports si
                WHERE si.id = products_raw.supplier_import_id
            )
            WHERE company_id IS NULL
        ');
        DB::statement('
            UPDATE import_errors
            SET company_id = (
                SELECT si.company_id FROM supplier_imports si
                WHERE si.id = import_errors.supplier_import_id
            )
            WHERE company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('import_errors', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
        Schema::table('products_raw', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
```

> Nota: confirmar o nome da coluna FK em `import_errors` para `supplier_import_id` (usada em `ImportUIController::errors`). Se o schema real usar outro nome, ajustar o `UPDATE` de backfill.

- [ ] **Step 4: Apply the trait to the four models**

Em cada um adicionar `use App\Models\Concerns\BelongsToCompany;` e `use BelongsToCompany;` na classe:
- `app/Models/Product.php`
- `app/Models/SupplierImport.php`
- `app/Models/ProductRaw.php`
- `app/Models/ImportError.php`

Exemplo para `Product` (que usa `$fillable`) — adicionar `'company_id'` ao array `$fillable` também:

```php
use App\Models\Concerns\BelongsToCompany;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sku', 'ean', 'name', 'brand', 'category',
        // ... resto inalterado
    ];
    // ...
}
```

Para `SupplierImport`, `ProductRaw`, `ImportError` (usam `$guarded = []`), basta o `use BelongsToCompany;`.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ChildTableScopeTest`
Expected: PASS.

- [ ] **Step 6: Run full suite (regression check)**

Run: `php artisan test`
Expected: PASS (nenhuma regressão nos testes anteriores).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_08_10_000001_add_company_id_to_child_tables.php app/Models/Product.php app/Models/SupplierImport.php app/Models/ProductRaw.php app/Models/ImportError.php tests/Feature/ChildTableScopeTest.php
git commit -m "feat: company_id em products_raw/import_errors + trait nos models de catálogo"
```

---

### Task 4: Models finos `Order`/`Listing` + isolamento nas telas de leitura

**Files:**
- Create: `app/Models/Order.php`, `app/Models/Listing.php`
- Modify: `app/Http/Controllers/Panel/OrderUIController.php`, `app/Http/Controllers/Panel/ListingUIController.php`
- Test: `tests/Feature/OrdersListingsIsolationTest.php`

**Interfaces:**
- Consumes: `BelongsToCompany` (Task 2).
- Produces: `App\Models\Order` (tabela `orders`, `$guarded = []`, trait) e `App\Models\Listing` (tabela `listings`, `$guarded = []`, trait). Controllers passam a paginar via Eloquent escopado.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OrdersListingsIsolationTest.php
namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrdersListingsIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_orders_index_returns_ok_and_scopes_to_current_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        // Colunas reais de `orders`: ml_order_id (unique), status enum, payload (json, obrigatório).
        Order::create(['company_id' => $a->id, 'ml_order_id' => 'A1', 'status' => 'paid', 'payload' => json_encode([])]);
        Order::create(['company_id' => $b->id, 'ml_order_id' => 'B1', 'status' => 'paid', 'payload' => json_encode([])]);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.orders.index'))->assertOk();
        $this->assertSame(['A1'], Order::pluck('ml_order_id')->all());
    }

    public function test_listings_index_scopes_to_current_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        // listings.product_id é obrigatório (FK), então cada listing precisa de um product.
        $this->setCurrentCompany($a);
        $pa = Product::create(['sku' => 'PA', 'name' => 'Produto A']);
        Listing::create(['company_id' => $a->id, 'product_id' => $pa->id, 'status' => 'draft']);

        $this->setCurrentCompany($b);
        $pb = Product::create(['sku' => 'PB', 'name' => 'Produto B']);
        Listing::create(['company_id' => $b->id, 'product_id' => $pb->id, 'status' => 'draft']);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.listings.index'))->assertOk();
        $this->assertCount(1, Listing::all());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OrdersListingsIsolationTest`
Expected: FAIL — models `Order`/`Listing` inexistentes.

- [ ] **Step 3: Create the models**

```php
<?php
// app/Models/Order.php
namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToCompany;

    protected $table = 'orders';
    protected $guarded = [];
}
```

```php
<?php
// app/Models/Listing.php
namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use BelongsToCompany;

    protected $table = 'listings';
    protected $guarded = [];
}
```

- [ ] **Step 4: Refactor the controllers to Eloquent**

```php
<?php
// app/Http/Controllers/Panel/OrderUIController.php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderUIController extends Controller {
    public function index(Request $r) {
        $orders = Order::orderByDesc('id')->paginate(20)->withQueryString();
        return view('panel.orders.index', compact('orders'));
    }
}
```

```php
<?php
// app/Http/Controllers/Panel/ListingUIController.php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingUIController extends Controller {
    public function index(Request $r) {
        $status = $r->get('status');

        $query = Listing::query()
            ->leftJoin('products', 'products.id', '=', 'listings.product_id')
            ->select('listings.*', 'products.name as product_name', 'products.sku');

        if ($status) {
            $query->where('listings.status', $status);
        }

        $listings = $query->orderByDesc('listings.id')->paginate(24)->withQueryString();
        $statuses = ['draft', 'ready', 'queued', 'published', 'paused', 'error'];

        return view('panel.listings.index', compact('listings', 'statuses', 'status'));
    }
}
```

> Nota: como `Listing` faz join com `products`, o `CompanyScope` qualifica `listings.company_id` (por usar `getTable()`), evitando ambiguidade. A view usa os mesmos campos de antes.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=OrdersListingsIsolationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Order.php app/Models/Listing.php app/Http/Controllers/Panel/OrderUIController.php app/Http/Controllers/Panel/ListingUIController.php tests/Feature/OrdersListingsIsolationTest.php
git commit -m "feat: models Order/Listing escopados + isolamento nas telas de leitura"
```

---

### Task 5: Refatorar `ImportUIController` para Eloquent escopado (fecha o vazamento)

**Files:**
- Modify: `app/Http/Controllers/Panel/ImportUIController.php`
- Test: `tests/Feature/ImportIsolationTest.php`

**Interfaces:**
- Consumes: models `SupplierImport`, `ProductRaw`, `ImportError`, `Supplier` com trait (Tasks 2–3); jobs `ProcessProductWithAI`.
- Produces: `ImportUIController` sem nenhuma `DB::table()` nos caminhos de tenant; acesso a recurso de outra empresa retorna 404.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ImportIsolationTest.php
namespace Tests\Feature;

use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ImportIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeImportFor($company): SupplierImport
    {
        return SupplierImport::create([
            'company_id' => $company->id,
            'supplier_name' => 'Fornecedor',
            'source_file' => 'x.csv',
            'source_type' => 'csv',
            'status' => 'done',
        ]);
    }

    public function test_user_cannot_view_other_company_import(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = $this->makeImportFor($b);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.imports.show', $importB->id))->assertNotFound();
    }

    public function test_user_cannot_delete_other_company_import(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = $this->makeImportFor($b);

        $this->actingAsCompanyUser($a);

        $this->delete(route('panel.imports.destroy', $importB->id))->assertNotFound();
        $this->assertDatabaseHas('supplier_imports', ['id' => $importB->id]);
    }

    public function test_user_can_view_own_company_import(): void
    {
        $a = $this->makeCompany('A');
        $importA = $this->makeImportFor($a);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.imports.show', $importA->id))->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ImportIsolationTest`
Expected: FAIL — hoje `show`/`destroy` usam `DB::table(...)->find($id)` sem escopo e retornam 200 para outra empresa.

- [ ] **Step 3: Refactor the controller**

Substituir `DB::table('supplier_imports')->find($id)` por `SupplierImport::findOrFail($id)` (o escopo restringe à empresa atual → 404 para outra empresa). Aplicar em `show`, `errors`, `exportErrors`, `processProducts`, `destroy`, `destroyItem`. Trocar consultas a `products_raw`/`import_errors` por Eloquent escopado. Trechos-chave:

```php
// index()
$companyId = auth()->user()->current_company_id;
$query = \App\Models\SupplierImport::query();
if ($search) {
    $query->where(function ($q) use ($search) {
        $q->where('supplier_name', 'like', "%$search%")
          ->orWhere('id', 'like', "%$search%")
          ->orWhere('source_type', 'like', "%$search%")
          ->orWhere('status', 'like', "%$search%");
    });
}
$imports = $query->orderByDesc('id')->paginate(12)->withQueryString();
$suppliers = \App\Models\Supplier::where('active', true)->orderBy('name')->get();

// show()
$imp = \App\Models\SupplierImport::findOrFail($id);
$query = \App\Models\ProductRaw::where('supplier_import_id', $imp->id);
// ... filtros de busca iguais, paginate(20)
$errorsCount = \App\Models\ImportError::where('supplier_import_id', $imp->id)->count();

// errors()
$imp = \App\Models\SupplierImport::findOrFail($id);
$errors = \App\Models\ImportError::where('supplier_import_id', $imp->id)
    ->orderBy('row_number')->paginate(50)->withQueryString();

// exportErrors()
$imp = \App\Models\SupplierImport::findOrFail($id);
$errors = \App\Models\ImportError::where('supplier_import_id', $imp->id)
    ->orderBy('row_number')->get();
// resto do stream inalterado

// processProducts()
$imp = \App\Models\SupplierImport::findOrFail($id);
// valida que cada product_raw pertence à empresa atual:
$rawIds = \App\Models\ProductRaw::where('supplier_import_id', $imp->id)
    ->whereIn('id', $r->input('product_ids'))->pluck('id');
foreach ($rawIds as $rawId) {
    \App\Jobs\ProcessProductWithAI::dispatch($rawId);
}
return back()->with('ok', $rawIds->count() . ' produto(s) enviado(s) para processamento com IA!');

// destroy()
$import = \App\Models\SupplierImport::findOrFail($id);
if ($import->source_file) { \Storage::disk('local')->delete($import->source_file); }
\App\Models\ImportError::where('supplier_import_id', $import->id)->delete();
\App\Models\ProductRaw::where('supplier_import_id', $import->id)->delete();
$import->delete();

// destroyItem()
$import = \App\Models\SupplierImport::findOrFail($importId);
$item = \App\Models\ProductRaw::where('id', $itemId)
    ->where('supplier_import_id', $import->id)->firstOrFail();
$item->delete();
```

Remover o `use Illuminate\Support\Facades\DB;` se não sobrar nenhum uso. Manter `Storage`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ImportIsolationTest`
Expected: PASS (3 testes).

- [ ] **Step 5: Run full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Panel/ImportUIController.php tests/Feature/ImportIsolationTest.php
git commit -m "fix: isolamento por empresa no ImportUIController (Eloquent escopado)"
```

---

### Task 6: Consolidar o pipeline de importação (remover auto-publicação)

**Files:**
- Delete: `app/Jobs/EnrichProduct.php`
- Modify: `app/Jobs/ImportSupplierFile.php`
- Test: `tests/Feature/ImportPipelineTest.php`

**Interfaces:**
- Produces: `ImportSupplierFile` deixa de disparar qualquer job de enriquecimento/publicação; grava `products_raw`/`import_errors` com `company_id` do `supplier_import`. Job `EnrichProduct` deixa de existir.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ImportPipelineTest.php
namespace Tests\Feature;

use App\Jobs\ImportSupplierFile;
use App\Jobs\PublishListingToML;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ImportPipelineTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_enrich_product_job_no_longer_exists(): void
    {
        $this->assertFalse(class_exists(\App\Jobs\EnrichProduct::class));
    }

    public function test_import_does_not_dispatch_any_publish_job(): void
    {
        Bus::fake();
        Storage::fake('local');

        $a = $this->makeCompany('A');
        $this->setCurrentCompany($a);

        // CSV mínimo com cabeçalho compatível com o mapeamento padrão do job.
        $csv = "sku,name,price\nSKU1,Produto 1,10.00\n";
        $path = 'supplier_imports/test.csv';
        Storage::disk('local')->put($path, $csv);

        $import = SupplierImport::create([
            'company_id' => $a->id,
            'supplier_name' => 'A',
            'source_file' => $path,
            'source_type' => 'csv',
            'status' => 'queued',
        ]);

        (new ImportSupplierFile($import->id))->handle();

        Bus::assertNotDispatched(PublishListingToML::class);
    }
}
```

> Nota: se o mapeamento/parse do CSV no job exigir colunas específicas, ajustar o cabeçalho do CSV de teste ao formato que `ImportSupplierFile` espera (ver o método `handle`). O foco do teste é: nenhum job de publicação é enfileirado.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ImportPipelineTest`
Expected: FAIL — `EnrichProduct` ainda existe e é disparado.

- [ ] **Step 3: Remove the auto-dispatch loop**

Em `app/Jobs/ImportSupplierFile.php`, remover o bloco de linhas 150-155:

```php
// REMOVER:
// Dispatcha jobs de enriquecimento apenas para linhas válidas
foreach ($rows as $row) {
    if (!empty($row['sku'])) {
        \App\Jobs\EnrichProduct::dispatch($this->importId, $row['sku']);
    }
}
```

- [ ] **Step 4: Delete the job**

```bash
git rm app/Jobs/EnrichProduct.php
```

- [ ] **Step 5: Ensure `products_raw`/`import_errors` gravam `company_id`**

Se `ImportSupplierFile::handle` grava `products_raw`/`import_errors` via `DB::table(...)->insert(...)`, incluir `company_id` = `company_id` do `supplier_import` sendo processado. Buscar o import no início do `handle` com `SupplierImport::withoutCompanyScope()->findOrFail($this->importId)` (job roda sem usuário) e usar `$import->company_id` nos inserts. Alternativamente, converter os inserts para `ProductRaw::create([... 'company_id' => $import->company_id ...])`.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=ImportPipelineTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Jobs/ImportSupplierFile.php tests/Feature/ImportPipelineTest.php
git commit -m "refactor: remover EnrichProduct e auto-publicação; import só popula products_raw"
```

---

### Task 7: Mover `convert-without-ai` para auth + remover `/import/supplier`

**Files:**
- Modify: `app/Http/Controllers/Panel/ImportUIController.php` (adicionar `convertWithoutAI`)
- Modify: `routes/web.php`
- Delete: `app/Http/Controllers/ImportController.php`
- Modify: `resources/views/panel/imports/show.blade.php:243` (atualizar URL do fetch)
- Test: `tests/Feature/ConvertWithoutAiTest.php`

**Interfaces:**
- Consumes: models escopados (Tasks 2–3).
- Produces: rota `POST /panel/imports/{importId}/items/{itemId}/convert` (nome `panel.imports.items.convert`), autenticada e escopada. Rotas `/import/supplier` e `/import/convert-without-ai` removidas. `App\Http\Controllers\ImportController` removido.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ConvertWithoutAiTest.php
namespace Tests\Feature;

use App\Models\ProductRaw;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ConvertWithoutAiTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_unauthenticated_legacy_routes_are_gone(): void
    {
        $this->post('/import/supplier')->assertNotFound();
        $this->post('/import/convert-without-ai')->assertNotFound();
    }

    public function test_converts_raw_product_of_own_company(): void
    {
        $a = $this->makeCompany('A');
        $import = SupplierImport::create(['company_id' => $a->id, 'supplier_name' => 'A', 'source_file' => 'a.csv', 'source_type' => 'csv', 'status' => 'done']);
        $raw = ProductRaw::create(['company_id' => $a->id, 'supplier_import_id' => $import->id, 'sku' => 'SKU1', 'name' => 'Produto 1', 'sale_price' => 10, 'status' => 'raw']);

        $this->actingAsCompanyUser($a);

        $this->post(route('panel.imports.items.convert', [$import->id, $raw->id]), ['stock' => 5])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('products', ['sku' => 'SKU1', 'company_id' => $a->id]);
    }

    public function test_cannot_convert_raw_product_of_other_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = SupplierImport::create(['company_id' => $b->id, 'supplier_name' => 'B', 'source_file' => 'b.csv', 'source_type' => 'csv', 'status' => 'done']);
        $rawB = ProductRaw::create(['company_id' => $b->id, 'supplier_import_id' => $importB->id, 'sku' => 'SKUB', 'name' => 'B', 'sale_price' => 10, 'status' => 'raw']);

        $this->actingAsCompanyUser($a);

        $this->post(route('panel.imports.items.convert', [$importB->id, $rawB->id]))->assertNotFound();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ConvertWithoutAiTest`
Expected: FAIL — rota nova inexistente; rotas antigas ainda respondem.

- [ ] **Step 3: Add `convertWithoutAI` to `ImportUIController`**

Adicionar método que recebe `importId` e `itemId`, resolve ambos via Eloquent escopado (`findOrFail` → 404 cross-tenant), cria o `Product` com `company_id` do usuário e atualiza o `ProductRaw`:

```php
public function convertWithoutAI(Request $r, int $importId, int $itemId)
{
    $r->validate([
        'description' => 'nullable|string',
        'stock' => 'nullable|integer|min:0',
    ]);

    $import = \App\Models\SupplierImport::findOrFail($importId);
    $raw = \App\Models\ProductRaw::where('supplier_import_id', $import->id)
        ->findOrFail($itemId);

    // Idempotência: gate por extra['product_id'] (o enum de products_raw NÃO tem 'ai_processed').
    $extra = $raw->extra ?? [];
    if (isset($extra['product_id'])) {
        return response()->json(['ok' => false, 'error' => 'Este produto já foi convertido', 'product_id' => $extra['product_id']], 400);
    }

    $description = $r->input('description') ?: $this->basicDescription($raw);

    $product = \App\Models\Product::create([
        'product_raw_id' => $raw->id,
        'sku' => $raw->sku,
        'ean' => $raw->ean,
        'name' => $raw->name,
        'brand' => $raw->brand,
        'description' => $description,
        'price' => $raw->sale_price,
        'cost_price' => $raw->cost_price,
        'status' => 'ready',
        'stock' => $r->input('stock', 0),
        // products.attributes é coluna json SEM cast no model Product → gravar string JSON.
        'attributes' => json_encode(['ai_generated' => false, 'manual_conversion' => true, 'source' => 'import']),
    ]);

    $extra['product_id'] = $product->id;
    $extra['converted_without_ai'] = true;
    $extra['converted_at'] = now()->toIso8601String();
    // products_raw.status enum válido: raw|normalized|enriched|ready.
    $raw->update(['status' => 'ready', 'extra' => $extra]);

    return response()->json(['ok' => true, 'product_id' => $product->id, 'message' => 'Produto convertido com sucesso sem IA']);
}

private function basicDescription(\App\Models\ProductRaw $raw): string
{
    $parts = array_filter([
        $raw->brand ? "Marca: {$raw->brand}" : null,
        $raw->name,
        $raw->sku ? "SKU: {$raw->sku}" : null,
        $raw->ean ? "EAN: {$raw->ean}" : null,
    ]);
    return implode("\n", $parts) ?: 'Produto sem descrição';
}
```

- [ ] **Step 4: Update routes**

Em `routes/web.php`: dentro do grupo `panel`, junto às rotas de imports, adicionar:

```php
Route::post('/imports/{importId}/items/{itemId}/convert', [ImportUIController::class, 'convertWithoutAI'])->name('imports.items.convert');
```

Remover as duas linhas finais fora do grupo auth (`/import/supplier` e `/import/convert-without-ai`) e o `use App\Http\Controllers\ImportController;`.

- [ ] **Step 5: Update the blade fetch URL**

Em `resources/views/panel/imports/show.blade.php` (linha ~243), trocar o endpoint e o payload do `fetch('/import/convert-without-ai', ...)` para a nova rota `panel.imports.items.convert` (usar `{{ route(...) }}` com os ids do import e do item), enviando `stock`/`description` no corpo e o header CSRF (`X-CSRF-TOKEN`).

- [ ] **Step 6: Delete the old controller**

```bash
git rm app/Http/Controllers/ImportController.php
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=ConvertWithoutAiTest`
Expected: PASS (3 testes).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Panel/ImportUIController.php routes/web.php resources/views/panel/imports/show.blade.php tests/Feature/ConvertWithoutAiTest.php
git commit -m "fix: convert-without-ai autenticado e escopado; remove /import/supplier"
```

---

### Task 8: Middleware — alimentar `CurrentCompany` + corrigir onboarding

**Files:**
- Modify: `app/Http/Middleware/EnsureUserHasCompany.php`
- Test: `tests/Feature/OnboardingRedirectTest.php`

**Interfaces:**
- Consumes: `CurrentCompany` (Task 1).
- Produces: middleware que, para usuário autenticado com empresa, chama `app(CurrentCompany::class)->set($id)`; usuário sem empresa é redirecionado para `panel.companies.create` (rota existente).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OnboardingRedirectTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OnboardingRedirectTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_user_without_company_is_redirected_to_company_creation(): void
    {
        $user = User::factory()->create(['current_company_id' => null]);
        $this->actingAs($user);

        $this->get(route('panel.dashboard'))
            ->assertRedirect(route('panel.companies.create'));
    }

    public function test_current_company_is_populated_for_user_with_company(): void
    {
        $company = $this->makeCompany('A');
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $user->companies()->attach($company->id, ['is_admin' => true]);
        $this->actingAs($user);

        $this->get(route('panel.dashboard'))->assertOk();
        $this->assertSame($company->id, app(\App\Support\CurrentCompany::class)->id());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=OnboardingRedirectTest`
Expected: FAIL — redirect aponta para `panel.companies.setup` (rota inexistente → erro), e `CurrentCompany` não é alimentado.

- [ ] **Step 3: Update the middleware**

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        return $next($request);
    }

    if (!$user->current_company_id) {
        $firstCompany = $user->companies()->first();

        if (!$firstCompany) {
            return redirect()->route('panel.companies.create')
                ->with('error', 'Você precisa criar/estar vinculado a uma empresa para acessar o sistema.');
        }

        $user->switchCompany($firstCompany->id);
    }

    app(\App\Support\CurrentCompany::class)->set($user->current_company_id);

    view()->share('currentCompany', $user->getCurrentCompany());

    return $next($request);
}
```

> Nota: se `panel.companies.create` também exigir passar pelo middleware (loop de redirect), garantir que a resposta de redirect não reentre no middleware para o mesmo usuário sem empresa. Como o middleware está no grupo `web` e a rota `companies.create` está sob `panel`/`auth`, o redirect para ela reentra no middleware — mas agora `create` é uma rota EXISTENTE e o usuário sem empresa cairá novamente no `redirect(create)`. Para evitar loop, adicionar guarda: se a rota atual já for `panel.companies.create` ou `panel.companies.store`, deixar passar:

```php
if (!$firstCompany) {
    if ($request->routeIs('panel.companies.create', 'panel.companies.store')) {
        app(\App\Support\CurrentCompany::class)->set(null);
        return $next($request);
    }
    return redirect()->route('panel.companies.create')->with('error', '...');
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=OnboardingRedirectTest`
Expected: PASS (2 testes).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/EnsureUserHasCompany.php tests/Feature/OnboardingRedirectTest.php
git commit -m "fix: middleware alimenta CurrentCompany e corrige redirect de onboarding"
```

---

### Task 9: Higiene do repositório

**Files:**
- Modify: `.gitignore`
- Remove (do versionamento): scripts soltos e arquivos temporários.

**Interfaces:**
- Produces: repositório sem scripts ad-hoc, logs ou temporários versionados.

- [ ] **Step 1: Untrack the loose files**

```bash
git rm --cached test_*.php check_*.php debug_attributes.php nul temp_original.txt php_errors.log 2>/dev/null || true
```

(Os arquivos permanecem no disco local; deixam de ser versionados.)

- [ ] **Step 2: Extend `.gitignore`**

Acrescentar ao final de `.gitignore`:

```
# Scripts ad-hoc e temporários
check_*.php
debug_*.php
temp_*.txt
/php_errors.log
```

(`test_*` e `*.log` já constam.)

- [ ] **Step 3: Verify nothing tracked remains**

Run: `git status --porcelain` e `git ls-files | grep -E "^(test_|check_|debug_|temp_).*|^nul$|php_errors.log"`
Expected: sem saída para o segundo comando (nada mais versionado).

- [ ] **Step 4: Full suite green + commit**

Run: `php artisan test`
Expected: PASS (suíte completa).

```bash
git add .gitignore
git commit -m "chore: remover scripts ad-hoc e temporários do versionamento"
```

---

## Notas finais de execução

- Rodar `php artisan test` (não `composer test`, que faz `config:clear` — funciona também, mas o filtro por task é mais rápido com `artisan test --filter`).
- Após todas as tasks, revisar manualmente as telas do painel (imports, produtos, listings, orders) logado como usuário de uma empresa, confirmando que nenhum dado de outra empresa aparece.
- Este plano cobre apenas o Subsistema A. Subsistemas B–E terão specs e planos próprios.
- **Acoplamento parcialmente resolvido:** o `CurrentCompany` (Task 1) elimina o acoplamento de *resolução de empresa*. A parte do `CompanyHelper` que renova *tokens* do ML instanciando o `IntegrationController` (`app(IntegrationController::class)`) NÃO é refatorada aqui — isso pertence ao serviço de integração e será tratado ao mexer no ML (Subsistemas B/E). Fica registrado como dívida conhecida.
- **Sanity check de rota:** confirmar que a rota `panel.orders.index` renderiza sem depender de `order_items`/produtos inexistentes nos testes; se a view exigir joins, ajustar o `OrderUIController` mantendo o escopo por empresa.
