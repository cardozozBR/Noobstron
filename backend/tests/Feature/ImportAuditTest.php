<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Jobs\ProcessImport;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ImportExecutionService;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAuditTest extends TestCase
{
    use RefreshDatabase;

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

        $user = User::create([
            'name' => 'Import Actor',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        return [
            $tenant,
            $user,
        ];
    }

    public function test_completed_import_is_audited(): void
    {
        Storage::fake('local');

        [$tenant, $user] =
            $this->environment(
                'import-audit-completed'
            );

        $this->actingAs($user);

        $import = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'data.csv',
                    "name\nMaria\n"
                ),
            ',',
            ImportTarget::LEADS
        );

        app(
            ImportExecutionService::class
        )->execute(
            $import
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'import.completed',
            ]
        );
    }

    public function test_failed_job_is_audited(): void
    {
        Storage::fake('local');

        [$tenant, $user] =
            $this->environment(
                'import-audit-failed'
            );

        $this->actingAs($user);

        $import = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'data.csv',
                    "name\nMaria\n"
                ),
            ',',
            ImportTarget::LEADS
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

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'import.failed',
            ]
        );
    }
}
