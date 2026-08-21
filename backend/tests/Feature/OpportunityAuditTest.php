<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
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

class OpportunityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::OPPORTUNITIES,
            true
        );

        $user = User::create([
            'name' => 'Opportunity Auditor',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        foreach ([
            PermissionEnum::OPPORTUNITIES_VIEW,
            PermissionEnum::OPPORTUNITIES_CREATE,
            PermissionEnum::OPPORTUNITIES_UPDATE,
            PermissionEnum::OPPORTUNITIES_DELETE,
        ] as $permissionEnum) {
            $permission = Permission::query()
                ->where(
                    'name',
                    $permissionEnum->value
                )
                ->firstOrFail();

            $user->permissions()
                ->syncWithoutDetaching(
                    $permission->id
                );
        }

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Cliente ' . $slug,
        ]);

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $stage = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        return [
            $tenant,
            $user,
            $customer,
            $pipeline,
            $stage,
        ];
    }

    public function test_opportunity_creation_is_audited(): void
    {
        [
            $tenant,
            $user,
            $customer,
            $pipeline,
            $stage,
        ] = $this->environment(
            'opportunity-audit-create'
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/opportunities",
                [
                    'name' => 'Contrato anual',
                    'customer_id' => $customer->id,
                    'pipeline_id' => $pipeline->id,
                    'pipeline_stage_id' => $stage->id,
                    'value_minor' => 250000,
                    'currency' => 'BRL',
                    'probability' => 50,
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'opportunity.created',
            ]
        );
    }

    public function test_opportunity_update_is_audited(): void
    {
        [
            $tenant,
            $user,
            $customer,
            $pipeline,
            $stage,
        ] = $this->environment(
            'opportunity-audit-update'
        );

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Contrato inicial',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 100000,
            'currency' => 'BRL',
            'probability' => 30,
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/opportunities/{$opportunity->id}",
                [
                    'name' => 'Contrato atualizado',
                    'value_minor' => 200000,
                    'currency' => 'BRL',
                    'probability' => 60,
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'opportunity.updated',
            ]
        );
    }

    public function test_stage_change_is_audited(): void
    {
        [
            $tenant,
            $user,
            $customer,
            $pipeline,
            $stage,
        ] = $this->environment(
            'opportunity-audit-stage'
        );

        $secondStage = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Negociacao',
            ]
        );

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Contrato em negociacao',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 300000,
            'currency' => 'BRL',
            'probability' => 70,
        ]);

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/opportunities/{$opportunity->id}/stage",
                [
                    'pipeline_stage_id' =>
                        $secondStage->id,
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'opportunity.stage_changed',
            ]
        );
    }

    public function test_opportunity_delete_is_audited(): void
    {
        [
            $tenant,
            $user,
            $customer,
            $pipeline,
            $stage,
        ] = $this->environment(
            'opportunity-audit-delete'
        );

        $opportunity = app(
            OpportunityService::class
        )->create([
            'name' => 'Contrato descartado',
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'value_minor' => 50000,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/opportunities/{$opportunity->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'opportunity.deleted',
            ]
        );
    }

    public function test_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $userA,
            $customerA,
            $pipelineA,
            $stageA,
        ] = $this->environment(
            'opportunity-audit-a'
        );

        $this
            ->actingAs($userA)
            ->post(
                "http://{$tenantA->slug}.localhost/opportunities",
                [
                    'name' => 'Oportunidade A',
                    'customer_id' => $customerA->id,
                    'pipeline_id' => $pipelineA->id,
                    'pipeline_stage_id' => $stageA->id,
                    'value_minor' => 100000,
                    'currency' => 'BRL',
                    'probability' => 50,
                ]
            )
            ->assertRedirect();

        [$tenantB] = $this->environment(
            'opportunity-audit-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            0,
            \App\Models\AuditLog::query()
                ->where(
                    'action',
                    'opportunity.created'
                )
                ->count()
        );
    }
}