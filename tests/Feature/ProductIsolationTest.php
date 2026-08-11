<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ProductIsolationTest extends TestCase
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

    private function validUpdatePayload(): array
    {
        return [
            'name' => 'Nome Alterado',
            'condition' => 'new',
            'price' => 99.90,
            'stock' => 5,
            'weight' => 100,
            'width' => 10,
            'height' => 10,
            'length' => 10,
        ];
    }

    public function test_user_cannot_view_other_company_product(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $productB = $this->makeProduct($b, 'B-1');

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.products.show', $productB->id))->assertNotFound();
    }

    public function test_user_cannot_update_other_company_product(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $productB = $this->makeProduct($b, 'B-1');

        $this->actingAsCompanyUser($a);

        $this->put(route('panel.products.update', $productB->id), $this->validUpdatePayload())
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'name' => 'Produto B-1',
        ]);
    }

    public function test_user_cannot_delete_other_company_product(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        $productB = $this->makeProduct($b, 'B-1');

        $this->actingAsCompanyUser($a);

        $this->delete(route('panel.products.destroy', $productB->id))->assertNotFound();
        $this->assertDatabaseHas('products', ['id' => $productB->id]);
    }

    public function test_user_can_view_own_company_product(): void
    {
        $a = $this->makeCompany('A');
        $productA = $this->makeProduct($a, 'A-1');

        $this->actingAsCompanyUser($a);

        $this->get(route('panel.products.show', $productA->id))->assertOk();
    }

    public function test_user_can_update_own_company_product(): void
    {
        $a = $this->makeCompany('A');
        $productA = $this->makeProduct($a, 'A-1');

        $this->actingAsCompanyUser($a);

        $this->put(route('panel.products.update', $productA->id), $this->validUpdatePayload())
            ->assertRedirect(route('panel.products.show', $productA->id));

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'name' => 'Nome Alterado',
        ]);
    }
}
