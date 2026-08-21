<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OpportunityService;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OpportunityHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Opportunity User',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function customer(
        Tenant $tenant,
        string $name = 'Cliente Teste'
    ): Customer {
        app(TenantContext::class)->set($tenant);

        return Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => $name,
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where('name', $permission->value)
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching($model->id);
    }

    private function pipelineEnvironment(
        Tenant $tenant
    ): array {
        app(TenantContext::class)->set($tenant);

        $pipeline = app(PipelineService::class)->create([
            'name' => 'Comercial',
        ]);

        $stage = app(PipelineStageService::class)->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        return [$pipeline, $stage];
    }

    private function opportunity(
        Tenant $tenant,
        string $name = 'Oportunidade Teste'
    ): Opportunity {
        $customer = $this->customer($tenant);

        [$pipeline, $stage] =
            $this->pipelineEnvironment($tenant);

        app(TenantContext::class)->set($tenant);

        return app(OpportunityService::class)->create([
            'name' => $name,
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 150000,
            'currency' => 'BRL',
            'probability' => 50,
        ]);
    }

    public function test_opportunity_routes_require_authentication(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-auth'
        );

        $this->get(
            "http://{$tenant->slug}.localhost/opportunities"
        )->assertRedirect('/login');
    }

    public function test_index_requires_opportunity_feature(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-feature'
        );

        $user = $this->user(
            $tenant,
            'feature@opportunities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/opportunities"
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-no-view'
        );

        $user = $this->user(
            $tenant,
            'no-view@opportunities.local'
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/opportunities"
            )
            ->assertForbidden();
    }

    public function test_user_with_feature_and_permission_can_access_index(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-index'
        );

        $user = $this->user(
            $tenant,
            'index@opportunities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/opportunities"
            );

        $response->assertOk();
        $response->assertSee(
            __('opportunities.title')
        );
        $response->assertSee(
            __('opportunities.index_description')
        );
    }

    public function test_store_creates_opportunity_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-store'
        );

        $user = $this->user(
            $tenant,
            'store@opportunities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_CREATE
        );

        $customer = $this->customer($tenant);

        [$pipeline, $stage] =
            $this->pipelineEnvironment($tenant);

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/opportunities",
                [
                    'name' => 'Nova oportunidade',
                    'customer_id' => $customer->id,
                    'pipeline_id' => $pipeline->id,
                    'pipeline_stage_id' => $stage->id,
                    'value_minor' => 150000,
                    'currency' => 'BRL',
                    'probability' => 50,
                ]
            );

        app(TenantContext::class)->set($tenant);

        $opportunity = Opportunity::query()
            ->where('name', 'Nova oportunidade')
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $opportunity->tenant_id
        );

        $this->assertSame(
            $customer->id,
            $opportunity->customer_id
        );

        $response->assertRedirect(
            route(
                'opportunities.edit',
                $opportunity->id
            )
        );
    }

    public function test_opportunity_from_other_tenant_cannot_be_edited(): void
    {
        $tenantA = $this->tenant(
            'opportunity-http-edit-a'
        );

        $opportunity = $this->opportunity(
            $tenantA,
            'Tenant A'
        );

        $tenantB = $this->tenant(
            'opportunity-http-edit-b'
        );

        $userB = $this->user(
            $tenantB,
            'edit-b@opportunities.local'
        );

        $this->grant(
            $userB,
            PermissionEnum::OPPORTUNITIES_UPDATE
        );

        $this
            ->actingAs($userB)
            ->get(
                "http://{$tenantB->slug}.localhost/opportunities/{$opportunity->id}/edit"
            )
            ->assertNotFound();
    }

    public function test_opportunity_can_move_to_stage(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-move'
        );

        $user = $this->user(
            $tenant,
            'move@opportunities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_UPDATE
        );

        $customer = $this->customer($tenant);

        [$pipeline, $firstStage] =
            $this->pipelineEnvironment($tenant);

        $secondStage = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Qualificado',
            ]
        );

        app(TenantContext::class)->set($tenant);

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Mover oportunidade',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $firstStage->id,
            'value_minor' => 200000,
            'currency' => 'BRL',
            'probability' => 60,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/opportunities/{$opportunity->id}/stage",
                [
                    'pipeline_stage_id' => $secondStage->id,
                ]
            );

        app(TenantContext::class)->set($tenant);

        $opportunity->refresh();

        $this->assertSame(
            $secondStage->id,
            $opportunity->pipeline_stage_id
        );

        $response->assertRedirect();
    }

    public function test_delete_requires_delete_permission(): void
    {
        $tenant = $this->tenant(
            'opportunity-http-delete'
        );

        $user = $this->user(
            $tenant,
            'delete@opportunities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::OPPORTUNITIES_VIEW
        );

        $opportunity = $this->opportunity(
            $tenant,
            'Preservada'
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/opportunities/{$opportunity->id}"
            )
            ->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            Opportunity::query()
                ->whereKey($opportunity->id)
                ->exists()
        );
    }
}