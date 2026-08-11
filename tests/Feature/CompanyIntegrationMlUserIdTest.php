<?php

namespace Tests\Feature;

use App\Models\CompanyIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyIntegrationMlUserIdTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_ml_user_id_column_exists_and_is_queryable(): void
    {
        $this->assertTrue(Schema::hasColumn('company_integrations', 'ml_user_id'));

        $a = $this->makeCompany('A');
        CompanyIntegration::create([
            'company_id' => $a->id,
            'integration_type' => 'mercado_livre',
            'active' => true,
            'ml_user_id' => '123456789',
            'credentials' => ['access_token' => 'x', 'user_id' => '123456789'],
        ]);

        $found = CompanyIntegration::where('integration_type', 'mercado_livre')
            ->where('ml_user_id', '123456789')->first();

        $this->assertNotNull($found);
        $this->assertSame($a->id, $found->company_id);
    }
}
