<?php

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
