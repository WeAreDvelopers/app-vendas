<?php
namespace Tests\Feature;

use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ImportIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeImportFor($company): SupplierImport
    {
        return SupplierImport::create([
            'company_id' => $company->id,
            'supplier_name' => 'Fornecedor',
            'source_file' => 'x.csv',
            'source_type' => 'csv',
            'status' => 'done',
        ]);
    }

    public function test_user_cannot_view_other_company_import(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = $this->makeImportFor($b);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.imports.show', $importB->id))->assertNotFound();
    }

    public function test_user_cannot_delete_other_company_import(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = $this->makeImportFor($b);

        $this->actingAsCompanyUser($a);

        $this->delete(route('panel.imports.destroy', $importB->id))->assertNotFound();
        $this->assertDatabaseHas('supplier_imports', ['id' => $importB->id]);
    }

    public function test_user_can_view_own_company_import(): void
    {
        $a = $this->makeCompany('A');
        $importA = $this->makeImportFor($a);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.imports.show', $importA->id))->assertOk();
    }
}
