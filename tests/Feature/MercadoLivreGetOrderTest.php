<?php

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
