<?php
namespace Tests\Feature;

use App\Jobs\PublishListingToML;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class MercadoLivrePublishIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function makeProduct(Company $c, string $sku): Product
    {
        return Product::withoutCompanyScope()->create([
            'company_id' => $c->id,
            'sku' => $sku,
            'name' => 'Produto ' . $sku,
            'price' => 10,
            'stock' => 1,
            'status' => 'ready',
        ]);
    }

    public function test_user_cannot_publish_other_company_product(): void
    {
        Bus::fake();

        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $productB = $this->makeProduct($b, 'B-1');

        // Company B has a ready-to-publish draft listing.
        DB::table('mercado_livre_listings')->insert([
            'product_id' => $productB->id,
            'title' => 'Anuncio B',
            'category_id' => 'MLB123',
            'price' => 10,
            'available_quantity' => 1,
            'condition' => 'new',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsCompanyUser($a);

        $this->post(route('panel.mercado-livre.publish', $productB->id))->assertNotFound();

        Bus::assertNotDispatched(PublishListingToML::class);
    }

    public function test_user_cannot_check_publish_status_of_other_company_product(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $productB = $this->makeProduct($b, 'B-1');

        DB::table('mercado_livre_listings')->insert([
            'product_id' => $productB->id,
            'title' => 'Anuncio B',
            'category_id' => 'MLB123',
            'price' => 10,
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.mercado-livre.publish-status', $productB->id))->assertNotFound();
    }
}
