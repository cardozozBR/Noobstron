<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\Tenant;
use App\Services\PipelineService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PipelineServiceTest extends TestCase
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
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    public function test_first_pipeline_becomes_default_automatically(): void
    {
        $this->tenant('pipeline-first');

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $this->assertTrue(
            $pipeline->is_default
        );
    }

    public function test_additional_pipeline_is_not_default_by_default(): void
    {
        $this->tenant('pipeline-multiple');

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
            'name' => 'Comercial',
        ]);

        $second = $service->create([
            'name' => 'Enterprise',
        ]);

        $this->assertTrue(
            $first->fresh()->is_default
        );

        $this->assertFalse(
            $second->is_default
        );
    }

    public function test_new_pipeline_can_replace_default(): void
    {
        $this->tenant('pipeline-replace');

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
            'name' => 'Comercial',
        ]);

        $second = $service->create([
            'name' => 'Enterprise',
            'is_default' => true,
        ]);

        $this->assertFalse(
            $first->fresh()->is_default
        );

        $this->assertTrue(
            $second->fresh()->is_default
        );
    }

    public function test_existing_pipeline_can_become_default(): void
    {
        $this->tenant('pipeline-set-default');

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
            'name' => 'Comercial',
        ]);

        $second = $service->create([
            'name' => 'Enterprise',
        ]);

        $service->setDefault(
            $second
        );

        $this->assertFalse(
            $first->fresh()->is_default
        );

        $this->assertTrue(
            $second->fresh()->is_default
        );

        $this->assertSame(
            1,
            Pipeline::query()
                ->where('is_default', true)
                ->count()
        );
    }

    public function test_setting_current_default_again_is_idempotent(): void
    {
        $this->tenant('pipeline-default-idempotent');

        $service = app(
            PipelineService::class
        );

        $pipeline = $service->create([
            'name' => 'Comercial',
        ]);

        $service->setDefault($pipeline);
        $service->setDefault($pipeline);

        $this->assertSame(
            1,
            Pipeline::query()
                ->where('is_default', true)
                ->count()
        );
    }

    public function test_unsetting_only_pipeline_keeps_it_default(): void
    {
        $this->tenant('pipeline-only-default');

        $service = app(
            PipelineService::class
        );

        $pipeline = $service->create([
            'name' => 'Comercial',
        ]);

        $updated = $service->update(
            $pipeline,
            [
                'is_default' => false,
            ]
        );

        $this->assertTrue(
            $updated->is_default
        );
    }

    public function test_unsetting_default_promotes_another_pipeline(): void
    {
        $this->tenant('pipeline-promote');

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
            'name' => 'Primeiro',
        ]);

        $second = $service->create([
            'name' => 'Segundo',
        ]);

        $updated = $service->update(
            $first,
            [
                'is_default' => false,
            ]
        );

        $this->assertFalse(
            $updated->is_default
        );

        $this->assertTrue(
            $second->fresh()->is_default
        );
    }

    public function test_deleting_default_promotes_another_pipeline(): void
    {
        $this->tenant('pipeline-delete-default');

        $service = app(
            PipelineService::class
        );

        $first = $service->create([
            'name' => 'Primeiro',
        ]);

        $second = $service->create([
            'name' => 'Segundo',
        ]);

        $service->delete(
            $first
        );

        $this->assertDatabaseMissing(
            'pipelines',
            [
                'id' => $first->id,
            ]
        );

        $this->assertTrue(
            $second->fresh()->is_default
        );
    }

    public function test_deleting_last_pipeline_is_allowed(): void
    {
        $this->tenant('pipeline-delete-last');

        $service = app(
            PipelineService::class
        );

        $pipeline = $service->create([
            'name' => 'Comercial',
        ]);

        $service->delete(
            $pipeline
        );

        $this->assertSame(
            0,
            Pipeline::query()->count()
        );
    }

    public function test_default_is_isolated_between_tenants(): void
    {
        $service = app(
            PipelineService::class
        );

        $tenantA = $this->tenant(
            'pipeline-default-a'
        );

        $pipelineA = $service->create([
            'name' => 'Comercial',
        ]);

        $tenantB = $this->tenant(
            'pipeline-default-b'
        );

        $pipelineB = $service->create([
            'name' => 'Comercial',
        ]);

        $this->assertTrue(
            $pipelineB->is_default
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertTrue(
            $pipelineA->fresh()->is_default
        );
    }

    public function test_database_rejects_two_defaults_for_same_tenant(): void
    {
        $this->tenant('pipeline-db-default');

        Pipeline::create([
            'name' => 'Primeiro',
            'is_default' => true,
        ]);

        $this->expectException(
            QueryException::class
        );

        Pipeline::create([
            'name' => 'Segundo',
            'is_default' => true,
        ]);
    }

    public function test_database_allows_default_in_different_tenants(): void
    {
        $this->tenant('pipeline-db-a');

        Pipeline::create([
            'name' => 'Comercial',
            'is_default' => true,
        ]);

        $this->tenant('pipeline-db-b');

        Pipeline::create([
            'name' => 'Comercial',
            'is_default' => true,
        ]);

        $this->assertSame(
            1,
            Pipeline::query()
                ->where('is_default', true)
                ->count()
        );
    }

    public function test_pipeline_name_is_normalized(): void
    {
        $this->tenant('pipeline-normalize');

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => '  Comercial  ',
            'description' => '  Principal  ',
        ]);

        $this->assertSame(
            'Comercial',
            $pipeline->name
        );

        $this->assertSame(
            'Principal',
            $pipeline->description
        );
    }

    public function test_empty_pipeline_name_is_rejected(): void
    {
        $this->tenant('pipeline-empty');

        $this->expectException(
            RuntimeException::class
        );

        app(
            PipelineService::class
        )->create([
            'name' => '   ',
        ]);
    }

    public function test_service_cannot_update_pipeline_from_other_tenant(): void
    {
        $service = app(
            PipelineService::class
        );

        $this->tenant('pipeline-service-a');

        $pipeline = $service->create([
            'name' => 'Comercial',
        ]);

        $this->tenant('pipeline-service-b');

        $this->expectException(
            ModelNotFoundException::class
        );

        $service->update(
            $pipeline,
            [
                'name' => 'Inválido',
            ]
        );
    }

    public function test_service_cannot_delete_pipeline_from_other_tenant(): void
    {
        $service = app(
            PipelineService::class
        );

        $this->tenant('pipeline-delete-a');

        $pipeline = $service->create([
            'name' => 'Comercial',
        ]);

        $this->tenant('pipeline-delete-b');

        $this->expectException(
            ModelNotFoundException::class
        );

        $service->delete(
            $pipeline
        );
    }
}
