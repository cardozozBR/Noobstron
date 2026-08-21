<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PipelineStageServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function pipeline(
        string $slug =
            'pipeline-stage-service'
    ): Pipeline {
        $this->tenant($slug);

        return app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);
    }

    public function test_stage_is_appended_by_default(): void
    {
        $pipeline = $this->pipeline();

        $service = app(
            PipelineStageService::class
        );

        $first = $service->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        $second = $service->create(
            $pipeline,
            [
                'name' => 'Qualificação',
            ]
        );

        $this->assertSame(
            1,
            $first->position
        );

        $this->assertSame(
            2,
            $second->position
        );
    }

    public function test_stage_can_be_inserted_in_middle(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-middle'
        );

        $service = app(
            PipelineStageService::class
        );

        $service->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        $service->create(
            $pipeline,
            [
                'name' => 'Proposta',
            ]
        );

        $service->create(
            $pipeline,
            [
                'name' => 'Qualificação',
                'position' => 2,
            ]
        );

        $this->assertSame(
            [
                'Novo',
                'Qualificação',
                'Proposta',
            ],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_stage_can_move_up(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-up'
        );

        $service = app(
            PipelineStageService::class
        );

        $one = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $two = $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $three = $service->create(
            $pipeline,
            ['name' => 'C']
        );

        $service->update(
            $three,
            [
                'position' => 1,
            ]
        );

        $this->assertSame(
            ['C', 'A', 'B'],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_stage_can_move_down(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-down'
        );

        $service = app(
            PipelineStageService::class
        );

        $one = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $service->create(
            $pipeline,
            ['name' => 'C']
        );

        $service->update(
            $one,
            [
                'position' => 3,
            ]
        );

        $this->assertSame(
            ['B', 'C', 'A'],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_stage_update_can_change_name_and_status(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-update'
        );

        $service = app(
            PipelineStageService::class
        );

        $stage = $service->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        $updated = $service->update(
            $stage,
            [
                'name' =>
                    ' Qualificação ',
                'is_active' => false,
            ]
        );

        $this->assertSame(
            'Qualificação',
            $updated->name
        );

        $this->assertFalse(
            $updated->is_active
        );
    }

    public function test_deleting_stage_compacts_positions(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-delete'
        );

        $service = app(
            PipelineStageService::class
        );

        $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $middle = $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $service->create(
            $pipeline,
            ['name' => 'C']
        );

        $service->delete(
            $middle
        );

        $this->assertSame(
            [
                1,
                2,
            ],
            $pipeline->stages()
                ->pluck('position')
                ->all()
        );

        $this->assertSame(
            [
                'A',
                'C',
            ],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_full_reorder_is_supported(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-reorder'
        );

        $service = app(
            PipelineStageService::class
        );

        $a = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $b = $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $c = $service->create(
            $pipeline,
            ['name' => 'C']
        );

        $service->reorder(
            $pipeline,
            [
                $c->id,
                $a->id,
                $b->id,
            ]
        );

        $this->assertSame(
            ['C', 'A', 'B'],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );

        $this->assertSame(
            [1, 2, 3],
            $pipeline->stages()
                ->pluck('position')
                ->all()
        );
    }

    public function test_reorder_is_idempotent(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-idempotent'
        );

        $service = app(
            PipelineStageService::class
        );

        $a = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $b = $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $order = [
            $a->id,
            $b->id,
        ];

        $service->reorder(
            $pipeline,
            $order
        );

        $service->reorder(
            $pipeline,
            $order
        );

        $this->assertSame(
            ['A', 'B'],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_reorder_rejects_duplicate_ids(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-duplicate'
        );

        $service = app(
            PipelineStageService::class
        );

        $a = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->reorder(
            $pipeline,
            [
                $a->id,
                $a->id,
            ]
        );
    }

    public function test_reorder_requires_all_pipeline_stages(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-missing'
        );

        $service = app(
            PipelineStageService::class
        );

        $a = $service->create(
            $pipeline,
            ['name' => 'A']
        );

        $service->create(
            $pipeline,
            ['name' => 'B']
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->reorder(
            $pipeline,
            [
                $a->id,
            ]
        );
    }

    public function test_reorder_rejects_stage_from_another_pipeline(): void
    {
        $this->tenant(
            'pipeline-stage-cross'
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

        $a = $stageService->create(
            $pipelineA,
            ['name' => 'A1']
        );

        $b = $stageService->create(
            $pipelineB,
            ['name' => 'B1']
        );

        $this->expectException(
            RuntimeException::class
        );

        $stageService->reorder(
            $pipelineA,
            [
                $a->id,
                $b->id,
            ]
        );
    }

    public function test_invalid_position_is_rejected(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-invalid-position'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'A',
                'position' => 0,
            ]
        );
    }

    public function test_empty_stage_name_is_rejected(): void
    {
        $pipeline = $this->pipeline(
            'pipeline-stage-empty'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => '   ',
            ]
        );
    }

    public function test_stage_from_other_tenant_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'pipeline-stage-tenant-a'
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

        $this->tenant(
            'pipeline-stage-tenant-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            PipelineStageService::class
        )->update(
            $stage,
            [
                'name' => 'Inválido',
            ]
        );
    }

    public function test_stage_from_other_tenant_cannot_be_deleted(): void
    {
        $this->tenant(
            'pipeline-stage-delete-a'
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

        $this->tenant(
            'pipeline-stage-delete-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            PipelineStageService::class
        )->delete(
            $stage
        );
    }
}
