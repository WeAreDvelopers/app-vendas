<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class MercadoLivreWebhookSecretTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private array $payload = [
        'topic' => 'orders_v2',
        'resource' => '/orders/2000123',
        'user_id' => 777,
    ];

    private function integrate(): void
    {
        $company = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $company->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'valid-token', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);
    }

    public function test_rejects_when_secret_configured_but_missing(): void
    {
        config(['services.mercado_livre.webhook_secret' => 's3cr3t']);
        Http::fake();

        $res = $this->postJson('/api/webhooks/mercado-livre', $this->payload);

        $res->assertStatus(403);
        $this->assertSame(0, Order::withoutCompanyScope()->count());
        Http::assertNothingSent();
    }

    public function test_rejects_wrong_secret(): void
    {
        config(['services.mercado_livre.webhook_secret' => 's3cr3t']);
        Http::fake();

        $res = $this->postJson('/api/webhooks/mercado-livre?secret=wrong', $this->payload);

        $res->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_accepts_correct_secret_via_query(): void
    {
        config(['services.mercado_livre.webhook_secret' => 's3cr3t']);
        $this->integrate();
        Http::fake([
            'api.mercadolibre.com/orders/*' => Http::response([
                'id' => '2000123', 'status' => 'paid', 'total_amount' => 10, 'order_items' => [],
            ], 200),
        ]);

        $res = $this->postJson('/api/webhooks/mercado-livre?secret=s3cr3t', $this->payload);

        $res->assertStatus(200);
        $this->assertSame(1, Order::withoutCompanyScope()->where('ml_order_id', '2000123')->count());
    }

    public function test_accepts_correct_secret_via_header(): void
    {
        config(['services.mercado_livre.webhook_secret' => 's3cr3t']);
        $this->integrate();
        Http::fake([
            'api.mercadolibre.com/orders/*' => Http::response([
                'id' => '2000123', 'status' => 'paid', 'total_amount' => 10, 'order_items' => [],
            ], 200),
        ]);

        $res = $this->postJson('/api/webhooks/mercado-livre', $this->payload, [
            'X-Webhook-Secret' => 's3cr3t',
        ]);

        $res->assertStatus(200);
        $this->assertSame(1, Order::withoutCompanyScope()->where('ml_order_id', '2000123')->count());
    }

    public function test_allows_when_no_secret_configured(): void
    {
        config(['services.mercado_livre.webhook_secret' => null]);
        $this->integrate();
        Http::fake([
            'api.mercadolibre.com/orders/*' => Http::response([
                'id' => '2000123', 'status' => 'paid', 'total_amount' => 10, 'order_items' => [],
            ], 200),
        ]);

        $res = $this->postJson('/api/webhooks/mercado-livre', $this->payload);

        $res->assertStatus(200);
        $this->assertSame(1, Order::withoutCompanyScope()->where('ml_order_id', '2000123')->count());
    }
}
