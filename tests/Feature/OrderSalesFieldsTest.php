<?php

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
