<?php

namespace Tests\Feature;

use App\Models\ProductRaw;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ConvertWithoutAiTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_unauthenticated_legacy_routes_are_gone(): void
    {
        $this->post('/import/supplier')->assertNotFound();
        $this->post('/import/convert-without-ai')->assertNotFound();
    }

    public function test_converts_raw_product_of_own_company(): void
    {
        $a = $this->makeCompany('A');
        $import = SupplierImport::create(['company_id' => $a->id, 'supplier_name' => 'A', 'source_file' => 'a.csv', 'source_type' => 'csv', 'status' => 'done']);
        $raw = ProductRaw::create(['company_id' => $a->id, 'supplier_import_id' => $import->id, 'sku' => 'SKU1', 'name' => 'Produto 1', 'sale_price' => 10, 'status' => 'raw']);

        $this->actingAsCompanyUser($a);

        $this->post(route('panel.imports.items.convert', [$import->id, $raw->id]), ['stock' => 5])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('products', ['sku' => 'SKU1', 'company_id' => $a->id]);
    }

    public function test_cannot_convert_raw_product_of_other_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $importB = SupplierImport::create(['company_id' => $b->id, 'supplier_name' => 'B', 'source_file' => 'b.csv', 'source_type' => 'csv', 'status' => 'done']);
        $rawB = ProductRaw::create(['company_id' => $b->id, 'supplier_import_id' => $importB->id, 'sku' => 'SKUB', 'name' => 'B', 'sale_price' => 10, 'status' => 'raw']);

        $this->actingAsCompanyUser($a);

        $this->post(route('panel.imports.items.convert', [$importB->id, $rawB->id]))->assertNotFound();
    }
}
