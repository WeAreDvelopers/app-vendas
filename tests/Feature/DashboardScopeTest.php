<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class DashboardScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeProduct(Company $c, string $sku): Product
    {
        return Product::withoutCompanyScope()->create([
            'company_id' => $c->id,
            'sku' => $sku,
            'name' => 'Produto ' . $sku,
            'price' => 10,
            'stock' => 1,
            'status' => 'ready',
        ]);
    }

    private function makeOrder(Company $c, string $mlOrderId): Order
    {
        return Order::withoutCompanyScope()->create([
            'company_id' => $c->id,
            'ml_order_id' => $mlOrderId,
            'status' => 'paid',
            'payload' => json_encode(['x' => 1]),
        ]);
    }

    private function makeImport(Company $c): SupplierImport
    {
        return SupplierImport::withoutCompanyScope()->create([
            'company_id' => $c->id,
            'supplier_name' => 'F',
            'source_file' => 'x.csv',
            'source_type' => 'csv',
            'status' => 'done',
        ]);
    }

    private function makeListing(Company $c, Product $p): Listing
    {
        return Listing::withoutCompanyScope()->create([
            'company_id' => $c->id,
            'product_id' => $p->id,
            'status' => 'draft',
        ]);
    }

    public function test_dashboard_counts_and_recent_orders_are_scoped_to_current_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        // Company A: 2 products, 2 orders, 1 import, 1 listing
        $pa1 = $this->makeProduct($a, 'A-1');
        $this->makeProduct($a, 'A-2');
        $this->makeOrder($a, 'A-100');
        $this->makeOrder($a, 'A-101');
        $this->makeImport($a);
        $this->makeListing($a, $pa1);

        // Company B: 1 product, 3 orders, 2 imports, 1 listing (noise)
        $pb1 = $this->makeProduct($b, 'B-1');
        $this->makeOrder($b, 'B-100');
        $this->makeOrder($b, 'B-101');
        $this->makeOrder($b, 'B-102');
        $this->makeImport($b);
        $this->makeImport($b);
        $this->makeListing($b, $pb1);

        $this->actingAsCompanyUser($a);

        $response = $this->get(route('panel.dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');
        $this->assertSame(2, (int) $stats['products']);
        $this->assertSame(2, (int) $stats['orders']);
        $this->assertSame(1, (int) $stats['imports']);
        $this->assertSame(1, (int) $stats['listings']);

        $recentOrders = $response->viewData('recentOrders');
        $this->assertCount(2, $recentOrders);
        foreach ($recentOrders as $order) {
            $this->assertSame($a->id, (int) $order->company_id);
        }
    }
}
