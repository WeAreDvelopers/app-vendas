<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class MercadoLivreOrderWebhookTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function integrate($company, string $mlUserId): void
    {
        CompanyIntegration::create([
            'company_id' => $company->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => $mlUserId,
            'credentials' => ['access_token' => 'valid-token', 'user_id' => $mlUserId],
            'expires_at' => now()->addHour(),
        ]);
    }

    private function orderPayload(string $id): array
    {
        return [
            'id' => $id,
            'status' => 'paid',
            'total_amount' => 150.0,
            'currency_id' => 'BRL',
            'buyer' => ['id' => 42, 'nickname' => 'COMPRADOR'],
            'order_items' => [[
                'item' => ['id' => 'MLB123', 'title' => 'Produto X', 'seller_sku' => 'SKU-1'],
                'quantity' => 2,
                'unit_price' => 75.0,
            ]],
        ];
    }

    public function test_orders_v2_webhook_ingests_order_scoped_to_company(): void
    {
        $company = $this->makeCompany('A');
        $this->integrate($company, '777');

        Http::fake([
            'api.mercadolibre.com/orders/2000*' => Http::response($this->orderPayload('2000123'), 200),
        ]);

        $res = $this->postJson('/api/webhooks/mercado-livre', [
            'topic' => 'orders_v2',
            'resource' => '/orders/2000123',
            'user_id' => 777,
        ]);

        $res->assertStatus(200);

        $order = Order::withoutCompanyScope()->where('ml_order_id', '2000123')->first();
        $this->assertNotNull($order);
        $this->assertSame($company->id, $order->company_id);
        $this->assertSame('paid', $order->status);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->qty);
    }

    public function test_webhook_ignores_unknown_ml_user(): void
    {
        // Nenhuma integração cadastrada para este user_id.
        Http::fake();

        $res = $this->postJson('/api/webhooks/mercado-livre', [
            'topic' => 'orders_v2',
            'resource' => '/orders/999',
            'user_id' => 555,
        ]);

        $res->assertStatus(200);
        $this->assertSame(0, Order::withoutCompanyScope()->count());
        Http::assertNothingSent();
    }

    public function test_redelivered_webhook_is_idempotent(): void
    {
        $company = $this->makeCompany('A');
        $this->integrate($company, '777');

        Http::fake([
            'api.mercadolibre.com/orders/*' => Http::response($this->orderPayload('2000123'), 200),
        ]);

        $payload = [
            'topic' => 'orders_v2',
            'resource' => '/orders/2000123',
            'user_id' => 777,
        ];

        $this->postJson('/api/webhooks/mercado-livre', $payload)->assertStatus(200);
        $this->postJson('/api/webhooks/mercado-livre', $payload)->assertStatus(200);

        $this->assertSame(1, Order::withoutCompanyScope()->where('ml_order_id', '2000123')->count());
        $this->assertSame(1, Order::withoutCompanyScope()->where('ml_order_id', '2000123')->first()->items->count());
    }
}
