<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use App\Support\MercadoLivre\CompanyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyResolverTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_resolves_company_for_known_ml_user(): void
    {
        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id, 'integration_type' => 'mercado_livre',
            'active' => true, 'ml_user_id' => '777', 'credentials' => ['user_id' => '777'],
        ]);

        $resolver = new CompanyResolver();
        $this->assertSame($a->id, $resolver->companyIdForMlUser('777'));
        $this->assertSame($a->id, $resolver->companyIdForMlUser(777));
    }

    public function test_returns_null_for_unknown_ml_user(): void
    {
        $resolver = new CompanyResolver();
        $this->assertNull($resolver->companyIdForMlUser('does-not-exist'));
    }
}
