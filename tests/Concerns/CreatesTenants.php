<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;

trait CreatesTenants
{
    protected function makeCompany(string $name = 'Acme'): Company
    {
        // company_id não se aplica a Company; criação direta.
        return Company::create(['name' => $name]);
    }

    protected function setCurrentCompany(Company $company): void
    {
        app(CurrentCompany::class)->set($company->id);
    }

    protected function actingAsCompanyUser(Company $company): User
    {
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $user->companies()->attach($company->id, ['is_admin' => true]);
        $this->actingAs($user);
        $this->setCurrentCompany($company);
        return $user;
    }
}
