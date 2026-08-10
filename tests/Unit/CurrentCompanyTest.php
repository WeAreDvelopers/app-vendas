<?php
// tests/Unit/CurrentCompanyTest.php
namespace Tests\Unit;

use App\Support\CurrentCompany;
use Tests\TestCase;

class CurrentCompanyTest extends TestCase
{
    public function test_stores_and_returns_the_active_company_id(): void
    {
        $current = new CurrentCompany();
        $this->assertNull($current->id());

        $current->set(42);
        $this->assertSame(42, $current->id());

        $current->clear();
        $this->assertNull($current->id());
    }

    public function test_is_bound_as_a_singleton(): void
    {
        $a = app(CurrentCompany::class);
        $b = app(CurrentCompany::class);
        $this->assertSame($a, $b);
    }
}
