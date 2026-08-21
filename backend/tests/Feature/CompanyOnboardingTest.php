<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug = 'onboarding-company'
    ): Tenant {
        return Tenant::query()->create([
            'name' => 'Empresa Onboarding',
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
        ]);
    }

    private function user(
        Tenant $tenant,
        string $email = 'admin@onboarding.test'
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Onboarding',
            'email' => $email,
            'password' => 'password123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function tenantRequest(
        Tenant $tenant,
        User $user,
        string $method,
        string $uri,
        array $data = []
    ) {
        $this->actingAs($user);

        $url = "http://{$tenant->slug}.localhost{$uri}";

        return match ($method) {
            'GET' => $this->get($url),
            'PUT' => $this->put($url, $data),
            default => throw new \RuntimeException(
                "Unsupported method: {$method}"
            ),
        };
    }

    public function test_company_onboarding_page_is_available_to_authenticated_tenant_user(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant);

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Dados da empresa')
            ->assertSee('Empresa Onboarding')
            ->assertSee('Brasil')
            ->assertSee('Português');
    }

    public function test_company_onboarding_prefills_existing_tenant_data(): void
    {
        $tenant = $this->tenant(
            'onboarding-company-prefill'
        );

        $user = $this->user(
            $tenant,
            'prefill@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee(
                'name="company_name"',
                false
            )
            ->assertSee(
                'value="Empresa Onboarding"',
                false
            )
            ->assertSee(
                'BR',
                false
            )
            ->assertSee(
                'pt-BR',
                false
            );
    }

    public function test_company_onboarding_can_update_company_name(): void
    {
        $tenant = $this->tenant(
            'onboarding-company-update'
        );

        $user = $this->user(
            $tenant,
            'update@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'PUT',
            '/onboarding/company',
            [
                'company_name' =>
                    'Empresa Onboarding Atualizada',
            ]
        );

        $response->assertRedirect(
            '/onboarding/company'
        );

        $this->assertDatabaseHas(
            'tenants',
            [
                'id' => $tenant->id,
                'name' =>
                    'Empresa Onboarding Atualizada',
            ]
        );

        $this->assertDatabaseMissing(
            'tenants',
            [
                'id' => $tenant->id,
                'name' => 'Empresa Onboarding',
            ]
        );
    }

    public function test_company_onboarding_does_not_change_slug_when_name_changes(): void
    {
        $tenant = $this->tenant(
            'stable-company-slug'
        );

        $user = $this->user(
            $tenant,
            'slug@onboarding.test'
        );

        $this->tenantRequest(
            $tenant,
            $user,
            'PUT',
            '/onboarding/company',
            [
                'company_name' =>
                    'Novo Nome Comercial',
            ]
        );

        $tenant->refresh();

        $this->assertSame(
            'Novo Nome Comercial',
            $tenant->name
        );

        $this->assertSame(
            'stable-company-slug',
            $tenant->slug
        );
    }

    public function test_company_onboarding_allows_segment_selection(): void
    {
        $tenant = $this->tenant(
            'onboarding-segment-selection'
        );

        $user = $this->user(
            $tenant,
            'segment-selection@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Segmento')
            ->assertSee(
                'name="segment"',
                false
            )
            ->assertSee('Serviços')
            ->assertSee('Comércio')
            ->assertSee('Indústria')
            ->assertSee('Tecnologia')
            ->assertSee('Outro');
    }

    public function test_company_onboarding_can_save_segment(): void
    {
        $tenant = $this->tenant(
            'onboarding-segment-save'
        );

        $user = $this->user(
            $tenant,
            'segment-save@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'PUT',
            '/onboarding/company',
            [
                'company_name' => $tenant->name,
                'segment' => 'technology',
            ]
        );

        $response->assertRedirect(
            '/onboarding/company'
        );

        $this->assertDatabaseHas(
            'tenants',
            [
                'id' => $tenant->id,
                'segment' => 'technology',
            ]
        );
    }

    public function test_company_onboarding_rejects_unknown_segment(): void
    {
        $tenant = $this->tenant(
            'onboarding-segment-invalid'
        );

        $user = $this->user(
            $tenant,
            'segment-invalid@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'PUT',
            '/onboarding/company',
            [
                'company_name' => $tenant->name,
                'segment' => 'segmento-arbitrario',
            ]
        );

        $response->assertSessionHasErrors(
            'segment'
        );
    }

    public function test_company_onboarding_presents_team_step(): void
    {
        $tenant = $this->tenant(
            'onboarding-team-step'
        );

        $user = $this->user(
            $tenant,
            'team-step@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Equipe');
    }

    public function test_company_onboarding_shows_current_team_member(): void
    {
        $tenant = $this->tenant(
            'onboarding-team-member'
        );

        $user = $this->user(
            $tenant,
            'team-member@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_company_onboarding_links_to_existing_user_creation(): void
    {
        $tenant = $this->tenant(
            'onboarding-team-add'
        );

        $user = $this->user(
            $tenant,
            'team-add@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Adicionar pessoa')
            ->assertSee(
                route('users.create'),
                false
            );
    }

    public function test_company_onboarding_presents_initial_pipeline_step(): void
    {
        $tenant = $this->tenant(
            'onboarding-initial-pipeline'
        );

        $user = $this->user(
            $tenant,
            'initial-pipeline@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Pipeline inicial');
    }

    public function test_company_onboarding_shows_default_pipeline(): void
    {
        $tenant = $this->tenant(
            'onboarding-default-pipeline'
        );

        $user = $this->user(
            $tenant,
            'default-pipeline@onboarding.test'
        );

        app(\App\Services\TenantContext::class)
            ->set($tenant);

        $pipeline = app(
            \App\Services\PipelineService::class
        )->create([
            'name' => 'Pipeline Comercial',
            'description' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee($pipeline->name);
    }

    public function test_company_onboarding_links_to_existing_pipeline_management(): void
    {
        $tenant = $this->tenant(
            'onboarding-pipeline-management'
        );

        $user = $this->user(
            $tenant,
            'pipeline-management@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Configurar pipeline')
            ->assertSee(
                route('pipelines.index'),
                false
            );
    }

    public function test_company_onboarding_presents_import_step(): void
    {
        $tenant = $this->tenant(
            'onboarding-import-step'
        );

        $user = $this->user(
            $tenant,
            'import-step@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Importação');
    }

    public function test_company_onboarding_explains_import_purpose(): void
    {
        $tenant = $this->tenant(
            'onboarding-import-purpose'
        );

        $user = $this->user(
            $tenant,
            'import-purpose@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee(
                'Importe seus dados existentes'
            )
            ->assertSee(
                'clientes e leads'
            );
    }

    public function test_company_onboarding_links_to_existing_import_creation(): void
    {
        $tenant = $this->tenant(
            'onboarding-import-link'
        );

        $user = $this->user(
            $tenant,
            'import-link@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Importar dados')
            ->assertSee(
                route('imports.create'),
                false
            );
    }

    public function test_company_onboarding_presents_checklist(): void
    {
        $tenant = $this->tenant(
            'onboarding-checklist'
        );

        $user = $this->user(
            $tenant,
            'checklist@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Checklist')
            ->assertSee('Dados da empresa')
            ->assertSee('Segmento')
            ->assertSee('Equipe')
            ->assertSee('Pipeline inicial')
            ->assertSee('Importação');
    }

    public function test_company_onboarding_marks_completed_objective_steps(): void
    {
        $tenant = $this->tenant(
            'onboarding-checklist-complete'
        );

        $tenant->update([
            'segment' => 'technology',
        ]);

        $user = $this->user(
            $tenant,
            'checklist-complete@onboarding.test'
        );

        app(\App\Services\TenantContext::class)
            ->set($tenant);

        app(
            \App\Services\PipelineService::class
        )->create([
            'name' => 'Pipeline Inicial',
            'description' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Dados da empresa')
            ->assertSee('Segmento')
            ->assertSee('Equipe')
            ->assertSee('Pipeline inicial')
            ->assertSee('Concluído');
    }

    public function test_company_onboarding_presents_import_as_optional(): void
    {
        $tenant = $this->tenant(
            'onboarding-checklist-import'
        );

        $user = $this->user(
            $tenant,
            'checklist-import@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Importação')
            ->assertSee('Opcional');
    }

    public function test_company_onboarding_presents_first_value_step(): void
    {
        $tenant = $this->tenant(
            'onboarding-first-value-step'
        );

        $user = $this->user(
            $tenant,
            'first-value-step@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Primeiro valor');
    }

    public function test_company_onboarding_explains_first_value_action(): void
    {
        $tenant = $this->tenant(
            'onboarding-first-value-copy'
        );

        $user = $this->user(
            $tenant,
            'first-value-copy@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Cadastre seu primeiro lead')
            ->assertSee(
                'iniciar o acompanhamento comercial'
            );
    }

    public function test_company_onboarding_links_to_existing_lead_creation(): void
    {
        $tenant = $this->tenant(
            'onboarding-first-value-link'
        );

        $user = $this->user(
            $tenant,
            'first-value-link@onboarding.test'
        );

        $response = $this->tenantRequest(
            $tenant,
            $user,
            'GET',
            '/onboarding/company'
        );

        $response
            ->assertOk()
            ->assertSee('Cadastrar primeiro lead')
            ->assertSee(
                route('leads.create'),
                false
            );
    }
}
