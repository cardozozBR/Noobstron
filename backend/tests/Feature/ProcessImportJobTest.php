<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Tenant;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessImportJobTest extends TestCase
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

    private function import(
        Tenant $tenant,
        string $content
    ): Import {
        app(TenantContext::class)->set(
            $tenant
        );

        return app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'data.csv',
                    $content
                ),
            ',',
            ImportTarget::LEADS
        );
    }

    public function test_job_processes_import(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-job'
        );

        $import = $this->import(
            $tenant,
            "name,email\n"
            . "Maria,maria@example.com\n"
        );

        $job = new ProcessImport(
            $import->id,
            $tenant->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $import->refresh();

        $this->assertSame(
            ImportStatus::COMPLETED,
            $import->status
        );

        $this->assertSame(
            1,
            $import->success_count
        );

        $this->assertDatabaseHas(
            'leads',
            [
                'tenant_id' => $tenant->id,
                'name' => 'Maria',
            ]
        );
    }

    public function test_job_restores_tenant_context(): void
    {
        Storage::fake('local');

        $tenantA = $this->tenant(
            'import-job-a'
        );

        $import = $this->import(
            $tenantA,
            "name\nMaria\n"
        );

        $tenantB = $this->tenant(
            'import-job-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $job = new ProcessImport(
            $import->id,
            $tenantA->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $this->assertDatabaseHas(
            'leads',
            [
                'tenant_id' => $tenantA->id,
                'name' => 'Maria',
            ]
        );

        $this->assertDatabaseMissing(
            'leads',
            [
                'tenant_id' => $tenantB->id,
                'name' => 'Maria',
            ]
        );
    }

    public function test_reexecuted_job_is_idempotent(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-job-idempotent'
        );

        $import = $this->import(
            $tenant,
            "name\nMaria\n"
        );

        $job = new ProcessImport(
            $import->id,
            $tenant->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        app()->call([
            $job,
            'handle',
        ]);

        $this->assertDatabaseCount(
            'leads',
            1
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            $import->fresh()
                ->rows()
                ->count()
        );
    }

    public function test_failed_marks_import_as_failed(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-job-failed'
        );

        $import = $this->import(
            $tenant,
            "name\nMaria\n"
        );

        $import->update([
            'status' =>
                ImportStatus::PROCESSING,
        ]);

        $job = new ProcessImport(
            $import->id,
            $tenant->id
        );

        $job->failed(
            new \RuntimeException(
                'Queue failure'
            )
        );

        $import->refresh();

        $this->assertSame(
            ImportStatus::FAILED,
            $import->status
        );

        $this->assertSame(
            'Queue failure',
            $import->error_message
        );

        $this->assertNotNull(
            $import->completed_at
        );
    }

    public function test_failed_does_not_override_completed_import(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-job-completed'
        );

        $import = $this->import(
            $tenant,
            "name\nMaria\n"
        );

        $job = new ProcessImport(
            $import->id,
            $tenant->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $job->failed(
            new \RuntimeException(
                'Late queue failure'
            )
        );

        $this->assertSame(
            ImportStatus::COMPLETED,
            $import->fresh()->status
        );
    }
}
