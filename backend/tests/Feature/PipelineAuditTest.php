<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PipelineAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
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
            Feature::PIPELINES,
            true
        );

        $user = User::create([
            'name' => 'Pipeline Auditor',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        foreach ([
            PermissionEnum::PIPELINES_VIEW,
            PermissionEnum::PIPELINES_CREATE,
            PermissionEnum::PIPELINES_UPDATE,
            PermissionEnum::PIPELINES_DELETE,
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

        return [
            $tenant,
            $user,
        ];
    }

    public function test_pipeline_creation_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-create'
            );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines",
                [
                    'name' => 'Comercial',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'pipeline.created',
            ]
        );
    }

    public function test_pipeline_update_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-update'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}",
                [
                    'name' => 'Enterprise',
                    'is_active' => true,
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'pipeline.updated',
            ]
        );
    }

    public function test_default_change_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-default'
            );

        $service = app(
            PipelineService::class
        );

        $service->create([
            'name' => 'Primeiro',
        ]);

        $second = $service->create([
            'name' => 'Segundo',
        ]);

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines/{$second->id}/default"
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'pipeline.default_changed',
            ]
        );
    }

    public function test_pipeline_delete_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-delete'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'pipeline.deleted',
            ]
        );
    }

    public function test_stage_creation_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-stage-create'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/stages",
                [
                    'name' => 'Novo',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'pipeline_stage.created',
            ]
        );
    }

    public function test_stage_update_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-stage-update'
            );

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

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/stages/{$stage->id}",
                [
                    'name' => 'Qualificação',
                    'position' => 1,
                    'is_active' => true,
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'pipeline_stage.updated',
            ]
        );
    }

    public function test_stage_delete_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-stage-delete'
            );

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

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/stages/{$stage->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'pipeline_stage.deleted',
            ]
        );
    }

    public function test_stage_reorder_is_audited(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-audit-stage-reorder'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $stageService = app(
            PipelineStageService::class
        );

        $a = $stageService->create(
            $pipeline,
            ['name' => 'A']
        );

        $b = $stageService->create(
            $pipeline,
            ['name' => 'B']
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/stages/reorder",
                [
                    'stage_ids' => [
                        $b->id,
                        $a->id,
                    ],
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'pipeline_stage.reordered',
            ]
        );
    }

    public function test_audit_is_isolated_between_tenants(): void
    {
        [$tenantA, $userA] =
            $this->environment(
                'pipeline-audit-a'
            );

        $this
            ->actingAs($userA)
            ->post(
                "http://{$tenantA->slug}.localhost/pipelines",
                [
                    'name' => 'Pipeline A',
                ]
            )
            ->assertRedirect();

        [$tenantB] =
            $this->environment(
                'pipeline-audit-b'
            );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            0,
            \App\Models\AuditLog::query()
                ->where(
                    'action',
                    'pipeline.created'
                )
                ->count()
        );
    }
}
