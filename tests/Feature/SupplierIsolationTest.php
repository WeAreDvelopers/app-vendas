<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class SupplierIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeSupplierFor(Company $company): Supplier
    {
        return Supplier::withoutCompanyScope()->create([
            'company_id' => $company->id,
            'name' => 'Fornecedor ' . $company->id,
            'code' => 'COD' . $company->id,
            'active' => true,
        ]);
    }

    /**
     * Authenticates a user of $company WITHOUT explicitly setting
     * CurrentCompany. This reproduces the production request pipeline where
     * implicit route-model binding (SubstituteBindings) resolves the model
     * BEFORE EnsureUserHasCompany sets CurrentCompany. Isolation must then
     * come solely from the CurrentCompany auth fallback.
     */
    private function loginWithoutExplicitCompany(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $user->companies()->attach($company->id, ['is_admin' => true]);
        $this->actingAs($user);
        app(CurrentCompany::class)->clear();
        return $user;
    }

    public function test_user_cannot_edit_other_company_supplier(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $supplierB = $this->makeSupplierFor($b);

        $this->loginWithoutExplicitCompany($a);

        $this->get(route('panel.suppliers.edit', $supplierB->id))->assertNotFound();
    }

    public function test_user_cannot_delete_other_company_supplier(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $supplierB = $this->makeSupplierFor($b);

        $this->loginWithoutExplicitCompany($a);

        $this->delete(route('panel.suppliers.destroy', $supplierB->id))->assertNotFound();
        $this->assertDatabaseHas('suppliers', ['id' => $supplierB->id]);
    }

    public function test_user_can_edit_own_company_supplier(): void
    {
        $a = $this->makeCompany('A');
        $supplierA = $this->makeSupplierFor($a);

        $this->loginWithoutExplicitCompany($a);

        $this->get(route('panel.suppliers.edit', $supplierA->id))->assertOk();
    }
}
