<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OnboardingRedirectTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_user_without_company_is_redirected_to_company_creation(): void
    {
        $user = User::factory()->create(['current_company_id' => null]);
        $this->actingAs($user);

        $this->get(route('panel.dashboard'))
            ->assertRedirect(route('panel.companies.create'));
    }

    public function test_current_company_is_populated_for_user_with_company(): void
    {
        $company = $this->makeCompany('A');
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $user->companies()->attach($company->id, ['is_admin' => true]);
        $this->actingAs($user);

        $this->get(route('panel.dashboard'))->assertOk();
        $this->assertSame($company->id, app(\App\Support\CurrentCompany::class)->id());
    }
}
