<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class BackfillMercadoLivreOrdersTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function integrate($company, string $mlUserId = '777'): void
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

    private function searchResponse(array $ids): array
    {
        return [
            'results' => array_map(fn ($id) => [
                'id' => $id,
                'status' => 'paid',
                'total_amount' => 50,
                'order_items' => [[
                    'item' => ['id' => 'MLB1', 'title' => 'X'],
                    'quantity' => 1,
                    'unit_price' => 50,
                ]],
            ], $ids),
            'paging' => ['total' => count($ids), 'offset' => 0, 'limit' => 50],
        ];
    }

    public function test_backfills_orders_for_company_without_notifying(): void
    {
        $company = $this->makeCompany('A');
        $this->integrate($company);
        // Usuário na empresa: se houvesse notificação, cairia em notifications.
        $this->actingAsCompanyUser($company);

        Http::fake([
            'api.mercadolibre.com/orders/search*' => Http::response($this->searchResponse(['O-1', 'O-2']), 200),
        ]);

        $this->artisan('ml:backfill-orders', ['company' => $company->id])
            ->assertExitCode(0);

        $this->assertSame(2, Order::withoutCompanyScope()->where('company_id', $company->id)->count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_backfill_is_idempotent_on_rerun(): void
    {
        $company = $this->makeCompany('A');
        $this->integrate($company);

        Http::fake([
            'api.mercadolibre.com/orders/search*' => Http::response($this->searchResponse(['O-1', 'O-2']), 200),
        ]);

        $this->artisan('ml:backfill-orders', ['company' => $company->id])->assertExitCode(0);
        $this->artisan('ml:backfill-orders', ['company' => $company->id])->assertExitCode(0);

        $this->assertSame(2, Order::withoutCompanyScope()->where('company_id', $company->id)->count());
    }

    public function test_rejects_invalid_since(): void
    {
        $company = $this->makeCompany('A');
        $this->integrate($company);
        Http::fake();

        $this->artisan('ml:backfill-orders', ['company' => $company->id, '--since' => 'not-a-date'])
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_no_active_integration_is_a_noop(): void
    {
        Http::fake();

        $this->artisan('ml:backfill-orders')->assertExitCode(0);

        $this->assertSame(0, Order::withoutCompanyScope()->count());
        Http::assertNothingSent();
    }
}
