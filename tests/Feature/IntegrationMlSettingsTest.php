<?php

namespace Tests\Feature;

use App\Helpers\IntegrationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class IntegrationMlSettingsTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_saves_auto_print_and_label_mode_per_company(): void
    {
        $a = $this->makeCompany('A');
        $this->actingAsCompanyUser($a);

        $res = $this->post(route('panel.integrations.ml.settings'), [
            'auto_print' => '0',
            'label_mode' => 'simple',
        ]);

        $res->assertRedirect(route('panel.integrations.index'));
        $this->assertFalse(IntegrationSettings::getMercadoLivreAutoPrint($a->id));
        $this->assertSame('simple', IntegrationSettings::getMercadoLivreLabelMode($a->id));
    }

    public function test_label_mode_is_validated(): void
    {
        $a = $this->makeCompany('A');
        $this->actingAsCompanyUser($a);

        $this->post(route('panel.integrations.ml.settings'), [
            'label_mode' => 'invalid',
        ])->assertSessionHasErrors('label_mode');
    }

    public function test_generates_and_regenerates_print_agent_token(): void
    {
        $a = $this->makeCompany('A');
        $this->actingAsCompanyUser($a);

        $this->post(route('panel.integrations.ml.print-token'))
            ->assertRedirect(route('panel.integrations.index'));

        $first = $a->fresh()->print_agent_token;
        $this->assertNotEmpty($first);
        $this->assertSame(48, strlen($first));

        // Regenerar troca o token.
        $this->post(route('panel.integrations.ml.print-token'));
        $second = $a->fresh()->print_agent_token;
        $this->assertNotSame($first, $second);
    }

    public function test_integrations_page_renders_with_options(): void
    {
        $a = $this->makeCompany('A');
        $this->actingAsCompanyUser($a);

        $res = $this->get(route('panel.integrations.index'));

        $res->assertStatus(200);
        $res->assertSee('Token do agente de impressão');
    }

    public function test_settings_default_to_global_config_when_unset(): void
    {
        config([
            'services.mercado_livre.auto_print' => true,
            'services.mercado_livre.label_mode' => 'auto',
        ]);
        $a = $this->makeCompany('A');

        // Nada salvo ainda → cai no fallback da config global.
        $this->assertTrue(IntegrationSettings::getMercadoLivreAutoPrint($a->id));
        $this->assertSame('auto', IntegrationSettings::getMercadoLivreLabelMode($a->id));
    }
}
