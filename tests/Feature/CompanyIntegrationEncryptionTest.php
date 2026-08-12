<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Services\MercadoLivreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyIntegrationEncryptionTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $a = $this->makeCompany('A');
        $integration = CompanyIntegration::create([
            'company_id' => $a->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'super-secret-token', 'refresh_token' => 'r', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);

        // Valor cru no banco: NÃO é o texto puro, NÃO dá pra json_decode como credenciais.
        $raw = DB::table('company_integrations')->where('id', $integration->id)->value('credentials');
        $this->assertStringNotContainsString('super-secret-token', $raw);
        $this->assertNull(json_decode($raw, true), 'credentials cru deveria ser payload criptografado, não JSON');

        // Via model, volta descriptografado.
        $this->assertSame('super-secret-token', $integration->fresh()->credentials['access_token']);
        $this->assertSame('super-secret-token', $integration->fresh()->getDecryptedCredentials()['access_token']);
    }

    public function test_service_reads_encrypted_token(): void
    {
        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'valid-token', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'api.mercadolibre.com/orders/ML-1' => Http::response(['id' => 'ML-1'], 200),
        ]);

        $order = app(MercadoLivreService::class)->getOrder($a->id, 'ML-1');

        $this->assertSame('ML-1', $order['id']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer valid-token'));
    }

    public function test_migration_encrypts_legacy_plaintext_rows(): void
    {
        $a = $this->makeCompany('A');
        // Simula linha legada: credenciais em texto puro (JSON), gravadas via DB cru.
        $id = DB::table('company_integrations')->insertGetId([
            'company_id' => $a->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '777',
            'credentials' => json_encode(['access_token' => 'legacy-token', 'user_id' => '777']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_08_11_000007_encrypt_existing_integration_credentials.php');
        $migration->up();

        // Agora está criptografado no banco, mas legível via model.
        $raw = DB::table('company_integrations')->where('id', $id)->value('credentials');
        $this->assertStringNotContainsString('legacy-token', $raw);
        $this->assertSame('legacy-token', CompanyIntegration::find($id)->credentials['access_token']);

        // Idempotência: rodar de novo não corrompe.
        $migration->up();
        $this->assertSame('legacy-token', CompanyIntegration::find($id)->credentials['access_token']);
    }
}
