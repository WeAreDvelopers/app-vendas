<?php
namespace Tests\Feature;

use App\Jobs\ImportSupplierFile;
use App\Jobs\PublishListingToML;
use App\Models\SupplierImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ImportPipelineTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_enrich_product_job_no_longer_exists(): void
    {
        $this->assertFalse(class_exists(\App\Jobs\EnrichProduct::class));
    }

    public function test_import_does_not_dispatch_any_publish_job(): void
    {
        Bus::fake();
        Storage::fake('local');

        $a = $this->makeCompany('A');
        $this->setCurrentCompany($a);

        // CSV mínimo com cabeçalho compatível com o mapeamento padrão do job.
        $csv = "sku,name,price\nSKU1,Produto 1,10.00\n";
        $path = 'supplier_imports/test.csv';
        Storage::disk('local')->put($path, $csv);

        $import = SupplierImport::create([
            'company_id' => $a->id,
            'supplier_name' => 'A',
            'source_file' => $path,
            'source_type' => 'csv',
            'status' => 'queued',
        ]);

        (new ImportSupplierFile($import->id))->handle();

        Bus::assertNotDispatched(PublishListingToML::class);
    }
}
