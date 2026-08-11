<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Supplier;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class CompanyScopeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_queries_only_return_current_company_records(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');

        Supplier::create(['name' => 'Fornecedor A', 'code' => 'FA', 'company_id' => $a->id, 'active' => true]);
        Supplier::create(['name' => 'Fornecedor B', 'code' => 'FB', 'company_id' => $b->id, 'active' => true]);

        $this->setCurrentCompany($a);
        $names = Supplier::pluck('name')->all();

        $this->assertSame(['Fornecedor A'], $names);
    }

    public function test_company_id_is_auto_filled_on_create(): void
    {
        $a = $this->makeCompany('A');
        $this->setCurrentCompany($a);

        $s = Supplier::create(['name' => 'Novo', 'code' => 'NOVO', 'active' => true]);

        $this->assertSame($a->id, $s->company_id);
    }

    public function test_without_company_scope_returns_all_records(): void
    {
        $a = $this->makeCompany('A');
        $b = $this->makeCompany('B');
        Supplier::create(['name' => 'Fornecedor A', 'code' => 'FA', 'company_id' => $a->id, 'active' => true]);
        Supplier::create(['name' => 'Fornecedor B', 'code' => 'FB', 'company_id' => $b->id, 'active' => true]);

        $this->setCurrentCompany($a);

        $this->assertCount(2, Supplier::withoutCompanyScope()->get());
    }

    public function test_no_filter_when_no_current_company(): void
    {
        $a = $this->makeCompany('A');
        Supplier::create(['name' => 'X', 'code' => 'X1', 'company_id' => $a->id, 'active' => true]);

        app(CurrentCompany::class)->clear();

        $this->assertCount(1, Supplier::all());
    }
}
