<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    private function user(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'name' => 'Import User',
            'email' =>
                'import@'
                . $tenant->slug
                . '.local',
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' => 'user',
        ]);
    }

    public function test_csv_can_be_stored_and_inspected(): void
    {
        Storage::fake('local');

        $tenant = $this->tenant(
            'import-upload'
        );

        $user = $this->user(
            $tenant
        );

        $this->actingAs($user);

        $file = UploadedFile::fake()
            ->createWithContent(
                'leads.csv',
                "name,email\n"
                . "Maria,maria@example.com\n"
                . "Joao,joao@example.com\n"
            );

        $import = app(
            ImportUploadService::class
        )->store(
            $file
        );

        $this->assertSame(
            $tenant->id,
            $import->tenant_id
        );

        $this->assertSame(
            $user->id,
            $import->user_id
        );

        $this->assertSame(
            ImportStatus::PARSED,
            $import->status
        );

        $this->assertSame(
            [
                'name',
                'email',
            ],
            $import->header
        );

        $this->assertSame(
            2,
            $import->row_count
        );

        Storage::disk('local')
            ->assertExists(
                $import->stored_path
            );
    }

    public function test_import_storage_is_isolated_by_tenant(): void
    {
        Storage::fake('local');

        $tenantA = $this->tenant(
            'import-storage-a'
        );

        $tenantB = $this->tenant(
            'import-storage-b'
        );

        $userA = $this->user(
            $tenantA
        );

        $this->actingAs($userA);

        $importA = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'a.csv',
                    "name\nA\n"
                )
        );

        $userB = $this->user(
            $tenantB
        );

        $this->actingAs($userB);

        $importB = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'b.csv',
                    "name\nB\n"
                )
        );

        $this->assertStringStartsWith(
            'tenant-imports/'
                . $tenantA->id
                . '/',
            $importA->stored_path
        );

        $this->assertStringStartsWith(
            'tenant-imports/'
                . $tenantB->id
                . '/',
            $importB->stored_path
        );

        $this->assertNotSame(
            $importA->stored_path,
            $importB->stored_path
        );
    }
}
