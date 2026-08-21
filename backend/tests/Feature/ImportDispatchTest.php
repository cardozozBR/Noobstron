<?php

namespace Tests\Feature;

use App\Enums\ImportTarget;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Tenant;
use App\Services\ImportDispatchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::create([
            'name' => 'Import Dispatch',
            'slug' => 'import-dispatch',
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

    public function test_import_job_is_dispatched(): void
    {
        Queue::fake();

        $tenant = $this->tenant();

        $import = Import::create([
            'target' => ImportTarget::LEADS,
            'original_name' => 'data.csv',
            'stored_path' => 'tenant-imports/data.csv',
        ]);

        app(
            ImportDispatchService::class
        )->dispatch(
            $import
        );

        Queue::assertPushed(
            ProcessImport::class,
            function (
                ProcessImport $job
            ) use (
                $tenant,
                $import
            ): bool {
                return
                    $job->importId
                        === $import->id
                    && $job->tenantId
                        === $tenant->id;
            }
        );
    }

    public function test_import_without_target_is_not_dispatched(): void
    {
        Queue::fake();

        $this->tenant();

        $import = Import::create([
            'original_name' => 'data.csv',
            'stored_path' => 'tenant-imports/data.csv',
        ]);

        try {
            app(
                ImportDispatchService::class
            )->dispatch(
                $import
            );

            $this->fail(
                'Expected RuntimeException.'
            );
        } catch (\RuntimeException) {
            Queue::assertNothingPushed();
        }
    }
}
