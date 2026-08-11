<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrderListingPanelTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeOrder(Company $c, array $attrs = []): Order
    {
        return Order::withoutCompanyScope()->create(array_merge([
            'company_id' => $c->id,
            'ml_order_id' => 'ORD-' . uniqid(),
            'status' => 'paid',
            'total_amount' => 100,
            'buyer_nickname' => 'COMPRADOR',
            'payload' => [],
        ], $attrs));
    }

    public function test_lists_only_current_company_orders(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $this->makeOrder($a, ['ml_order_id' => 'A-111', 'buyer_nickname' => 'ALICE']);
        $this->makeOrder($b, ['ml_order_id' => 'B-999', 'buyer_nickname' => 'BOB']);

        $this->actingAsCompanyUser($a);

        $res = $this->get(route('panel.orders.index'));

        $res->assertStatus(200);
        $res->assertSee('A-111');
        $res->assertDontSee('B-999');
        $res->assertDontSee('BOB');
    }

    public function test_search_filters_by_order_id_or_buyer(): void
    {
        $a = $this->makeCompany('A');
        $this->makeOrder($a, ['ml_order_id' => 'A-111', 'buyer_nickname' => 'ALICE']);
        $this->makeOrder($a, ['ml_order_id' => 'A-222', 'buyer_nickname' => 'CAROL']);

        $this->actingAsCompanyUser($a);

        $res = $this->get(route('panel.orders.index', ['q' => 'CAROL']));

        $res->assertStatus(200);
        $res->assertSee('A-222');
        $res->assertDontSee('A-111');
    }

    public function test_status_filter(): void
    {
        $a = $this->makeCompany('A');
        $this->makeOrder($a, ['ml_order_id' => 'A-PAID', 'status' => 'paid']);
        $this->makeOrder($a, ['ml_order_id' => 'A-SHIPPED', 'status' => 'shipped']);

        $this->actingAsCompanyUser($a);

        $res = $this->get(route('panel.orders.index', ['status' => 'shipped']));

        $res->assertStatus(200);
        $res->assertSee('A-SHIPPED');
        $res->assertDontSee('A-PAID');
    }

    public function test_show_renders_own_order_with_items(): void
    {
        $a = $this->makeCompany('A');
        $order = $this->makeOrder($a, ['ml_order_id' => 'A-777']);
        OrderItem::withoutCompanyScope()->create([
            'company_id' => $a->id,
            'order_id' => $order->id,
            'ml_item_id' => 'MLB1',
            'title' => 'Produto Detalhe',
            'qty' => 3,
            'price' => 25,
        ]);

        $this->actingAsCompanyUser($a);

        $res = $this->get(route('panel.orders.show', $order->id));

        $res->assertStatus(200);
        $res->assertSee('A-777');
        $res->assertSee('Produto Detalhe');
    }

    public function test_show_of_other_company_order_is_404(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $orderB = $this->makeOrder($b, ['ml_order_id' => 'B-777']);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.orders.show', $orderB->id))->assertStatus(404);
    }
}
