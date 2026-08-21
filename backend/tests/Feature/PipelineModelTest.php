<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PipelineModelTest extends TestCase
{
    use RefreshDatabase;

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

        app(TenantContext::class)->set($tenant);

        return $tenant;
    }

    public function test_pipeline_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant('pipeline-create');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
        ]);

        $this->assertSame(
            $tenant->id,
            $pipeline->tenant_id
        );
    }

    public function test_pipeline_boolean_fields_are_cast(): void
    {
        $this->tenant('pipeline-casts');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
            'is_default' => true,
            'is_active' => false,
        ]);

        $this->assertTrue($pipeline->is_default);
        $this->assertFalse($pipeline->is_active);
    }

    public function test_pipeline_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant('pipeline-a');

        Pipeline::create([
            'name' => 'Pipeline A',
        ]);

        $tenantB = $this->tenant('pipeline-b');

        Pipeline::create([
            'name' => 'Pipeline B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertSame(
            ['Pipeline A'],
            Pipeline::query()
                ->pluck('name')
                ->all()
        );

        app(TenantContext::class)->set($tenantB);

        $this->assertSame(
            ['Pipeline B'],
            Pipeline::query()
                ->pluck('name')
                ->all()
        );
    }

    public function test_pipeline_from_another_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant('pipeline-find-a');

        $this->tenant('pipeline-find-b');

        $foreign = Pipeline::create([
            'name' => 'Foreign',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertNull(
            Pipeline::query()->find($foreign->id)
        );
    }

    public function test_tenant_has_pipelines_relation(): void
    {
        $tenant = $this->tenant('pipeline-relation');

        Pipeline::create([
            'name' => 'Comercial',
        ]);

        $this->assertCount(
            1,
            $tenant->pipelines
        );
    }

    public function test_pipeline_stages_are_returned_in_position_order(): void
    {
        $this->tenant('pipeline-order');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Proposta',
            'position' => 2,
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Qualificação',
            'position' => 1,
        ]);

        $this->assertSame(
            [
                'Qualificação',
                'Proposta',
            ],
            $pipeline->stages()
                ->pluck('name')
                ->all()
        );
    }

    public function test_pipeline_stage_inherits_pipeline_tenant(): void
    {
        $tenant = $this->tenant('pipeline-stage-tenant');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
        ]);

        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Novo',
            'position' => 1,
        ]);

        $this->assertSame(
            $tenant->id,
            $stage->tenant_id
        );
    }

    public function test_stage_cannot_reference_pipeline_from_another_tenant(): void
    {
        $this->tenant('pipeline-stage-a');

        $pipeline = Pipeline::create([
            'name' => 'Pipeline A',
        ]);

        $this->tenant('pipeline-stage-b');

        $this->expectException(
            RuntimeException::class
        );

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Invalid',
            'position' => 1,
        ]);
    }

    public function test_stage_position_is_unique_inside_pipeline(): void
    {
        $this->tenant('pipeline-position');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Novo',
            'position' => 1,
        ]);

        $this->expectException(
            QueryException::class
        );

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Outro',
            'position' => 1,
        ]);
    }

    public function test_same_position_can_exist_in_different_pipelines(): void
    {
        $this->tenant('pipeline-position-other');

        $pipelineA = Pipeline::create([
            'name' => 'A',
        ]);

        $pipelineB = Pipeline::create([
            'name' => 'B',
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipelineA->id,
            'name' => 'Novo A',
            'position' => 1,
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipelineB->id,
            'name' => 'Novo B',
            'position' => 1,
        ]);

        $this->assertSame(
            2,
            PipelineStage::query()->count()
        );
    }

    public function test_stage_name_is_unique_inside_pipeline(): void
    {
        $this->tenant('pipeline-stage-name');

        $pipeline = Pipeline::create([
            'name' => 'Comercial',
        ]);

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Novo',
            'position' => 1,
        ]);

        $this->expectException(
            QueryException::class
        );

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Novo',
            'position' => 2,
        ]);
    }

    public function test_pipeline_name_is_unique_inside_tenant(): void
    {
        $this->tenant('pipeline-name');

        Pipeline::create([
            'name' => 'Comercial',
        ]);

        $this->expectException(
            QueryException::class
        );

        Pipeline::create([
            'name' => 'Comercial',
        ]);
    }

    public function test_same_pipeline_name_can_exist_between_tenants(): void
    {
        $this->tenant('pipeline-name-a');

        Pipeline::create([
            'name' => 'Comercial',
        ]);

        $this->tenant('pipeline-name-b');

        Pipeline::create([
            'name' => 'Comercial',
        ]);

        $this->assertSame(
            1,
            Pipeline::query()
                ->where('name', 'Comercial')
                ->count()
        );
    }
}
