<?php

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
