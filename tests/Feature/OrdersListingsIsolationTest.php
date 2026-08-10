<?php

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
