<?php

namespace Tests\Feature;

use App\Models\ProductRaw;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ChildTableScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_products_raw_is_scoped_by_company(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        $impA = SupplierImport::create(['company_id' => $a->id, 'supplier_name' => 'A', 'source_file' => 'a.csv', 'source_type' => 'csv', 'status' => 'queued']);
        $impB = SupplierImport::create(['company_id' => $b->id, 'supplier_name' => 'B', 'source_file' => 'b.csv', 'source_type' => 'csv', 'status' => 'queued']);

        ProductRaw::create(['company_id' => $a->id, 'supplier_import_id' => $impA->id, 'sku' => 'SKU-A', 'name' => 'A', 'status' => 'raw']);
        ProductRaw::create(['company_id' => $b->id, 'supplier_import_id' => $impB->id, 'sku' => 'SKU-B', 'name' => 'B', 'status' => 'raw']);

        $this->setCurrentCompany($a);

        $this->assertSame(['SKU-A'], ProductRaw::pluck('sku')->all());
        $this->assertSame(['A'], SupplierImport::pluck('supplier_name')->all());
    }
}
