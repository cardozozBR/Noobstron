<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineStage;
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

class PipelineManagementTest extends TestCase
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
        string $slug = 'pipeline-http'
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
            'name' => 'Pipeline User',
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

    public function test_user_with_permission_can_list_pipelines(): void
    {
        [$tenant, $user] =
            $this->environment();

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines"
            )
            ->assertOk();
    }

    public function test_user_without_view_permission_cannot_list_pipelines(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-no-view'
            );

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::PIPELINES_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->detach($permission->id);

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines"
            )
            ->assertForbidden();
    }

    public function test_pipeline_feature_is_required(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-feature-off'
            );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::PIPELINES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines"
            )
            ->assertForbidden();
    }

    public function test_pipeline_can_be_created(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-create'
            );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines",
                [
                    'name' => 'Comercial',
                    'description' => 'Principal',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $pipeline = Pipeline::query()
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'pipelines.edit',
                $pipeline->id
            )
        );

        $this->assertTrue(
            $pipeline->is_default
        );
    }

    public function test_pipeline_can_be_updated(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-update'
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
                    'description' => 'Grandes contas',
                    'is_active' => true,
                ]
            )
            ->assertRedirect(
                route(
                    'pipelines.edit',
                    $pipeline->id
                )
            );

        $this->assertDatabaseHas(
            'pipelines',
            [
                'id' => $pipeline->id,
                'name' => 'Enterprise',
            ]
        );
    }

    public function test_pipeline_can_be_set_as_default(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-default'
            );

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
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
            ->assertRedirect(
                route('pipelines.index')
            );

        $this->assertFalse(
            $first->fresh()->is_default
        );

        $this->assertTrue(
            $second->fresh()->is_default
        );
    }

    public function test_pipeline_can_be_deleted(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-delete'
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
            ->assertRedirect(
                route('pipelines.index')
            );

        $this->assertDatabaseMissing(
            'pipelines',
            [
                'id' => $pipeline->id,
            ]
        );
    }

    public function test_stage_can_be_created(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-stage-create'
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
            ->assertRedirect(
                route(
                    'pipelines.edit',
                    $pipeline->id
                )
            );

        $this->assertDatabaseHas(
            'pipeline_stages',
            [
                'pipeline_id' => $pipeline->id,
                'name' => 'Novo',
                'position' => 1,
            ]
        );
    }

    public function test_stage_can_be_updated(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-stage-update'
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
            ->assertRedirect(
                route(
                    'pipelines.edit',
                    $pipeline->id
                )
            );

        $this->assertDatabaseHas(
            'pipeline_stages',
            [
                'id' => $stage->id,
                'name' => 'Qualificação',
            ]
        );
    }

    public function test_stage_can_be_deleted(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-stage-delete'
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
            ->assertRedirect(
                route(
                    'pipelines.edit',
                    $pipeline->id
                )
            );

        $this->assertDatabaseMissing(
            'pipeline_stages',
            [
                'id' => $stage->id,
            ]
        );
    }

    public function test_stages_can_be_reordered(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-stage-reorder'
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

        $c = $stageService->create(
            $pipeline,
            ['name' => 'C']
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/stages/reorder",
                [
                    'stage_ids' => [
                        $c->id,
                        $a->id,
                        $b->id,
                    ],
                ]
            )
            ->assertRedirect(
                route(
                    'pipelines.edit',
                    $pipeline->id
                )
            );

        $this->assertSame(
            ['C', 'A', 'B'],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_pipeline_from_other_tenant_cannot_be_edited(): void
    {
        [$tenantA] =
            $this->environment(
                'pipeline-http-a'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        [$tenantB, $userB] =
            $this->environment(
                'pipeline-http-b'
            );

        $this
            ->actingAs($userB)
            ->get(
                "http://{$tenantB->slug}.localhost/pipelines/{$pipeline->id}/edit"
            )
            ->assertNotFound();
    }

    public function test_stage_from_other_pipeline_cannot_be_updated(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-http-stage-cross'
            );

        $pipelineService = app(
            PipelineService::class
        );

        $stageService = app(
            PipelineStageService::class
        );

        $pipelineA =
            $pipelineService->create([
                'name' => 'A',
            ]);

        $pipelineB =
            $pipelineService->create([
                'name' => 'B',
            ]);

        $stageB =
            $stageService->create(
                $pipelineB,
                ['name' => 'B1']
            );

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/pipelines/{$pipelineA->id}/stages/{$stageB->id}",
                [
                    'name' => 'Invalid',
                ]
            )
            ->assertNotFound();
    }
}
