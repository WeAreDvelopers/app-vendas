<?php
namespace Tests\Feature;

use App\Jobs\ImportSupplierFile;
use App\Jobs\PublishListingToML;
use App\Models\SupplierImport;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
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

        // CSV com uma linha válida (SKU1) e uma linha inválida sem nome
        // (SKU2), para exercitar tanto products_raw quanto import_errors.
        $csv = "sku,name,price\nSKU1,Produto 1,10.00\nSKU2,,20.00\n";
        $path = 'supplier_imports/test.csv';
        Storage::disk('local')->put($path, $csv);

        $import = SupplierImport::create([
            'company_id' => $a->id,
            'supplier_name' => 'A',
            'source_file' => $path,
            'source_type' => 'csv',
            'status' => 'queued',
        ]);

        // Reproduz o estado real do worker de fila: nenhum CurrentCompany
        // está vinculado quando o job roda em produção. Se o job voltasse a
        // depender do auto-fill do trait BelongsToCompany (que só age quando
        // há um CurrentCompany setado), os inserts abaixo ficariam com
        // company_id nulo e as asserções falhariam.
        app(CurrentCompany::class)->clear();

        (new ImportSupplierFile($import->id))->handle();

        Bus::assertNotDispatched(PublishListingToML::class);

        $rawCompanyId = DB::table('products_raw')
            ->where('supplier_import_id', $import->id)
            ->where('sku', 'SKU1')
            ->value('company_id');
        $this->assertSame($a->id, (int) $rawCompanyId);

        $errorCompanyId = DB::table('import_errors')
            ->where('supplier_import_id', $import->id)
            ->value('company_id');
        $this->assertNotNull($errorCompanyId, 'Expected an import_errors row for the invalid SKU2 line.');
        $this->assertSame($a->id, (int) $errorCompanyId);
    }
}
