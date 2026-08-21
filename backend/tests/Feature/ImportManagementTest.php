<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Enums\ImportTarget;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ImportUploadService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportManagementTest extends TestCase
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
        string $slug = 'imports-web'
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
            Feature::IMPORTS,
            true
        );

        $user = User::create([
            'name' => 'Import User',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        foreach ([
            PermissionEnum::IMPORTS_VIEW,
            PermissionEnum::IMPORTS_CREATE,
        ] as $permission) {
            $model = Permission::query()
                ->where(
                    'name',
                    $permission->value
                )
                ->firstOrFail();

            $user->permissions()
                ->syncWithoutDetaching(
                    $model->id
                );
        }

        return [
            $tenant,
            $user,
        ];
    }

    public function test_user_can_list_imports(): void
    {
        [$tenant, $user] =
            $this->environment();

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/imports"
            );

        $response->assertOk();

        $response->assertSee(
            __('imports.title'),
            false
        );
    }

    public function test_imports_require_view_permission(): void
    {
        [$tenant, $user] =
            $this->environment(
                'imports-no-view'
            );

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::IMPORTS_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->detach(
                $permission->id
            );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/imports"
            )
            ->assertForbidden();
    }

    public function test_import_feature_is_required(): void
    {
        [$tenant, $user] =
            $this->environment(
                'imports-feature-off'
            );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::IMPORTS,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/imports"
            )
            ->assertForbidden();
    }

    public function test_csv_can_be_uploaded_and_previewed(): void
    {
        Storage::fake('local');

        [$tenant, $user] =
            $this->environment(
                'imports-upload'
            );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/imports",
                [
                    'file' => UploadedFile::fake()
                        ->createWithContent(
                            'leads.csv',
                            "name,email\n"
                            . "Maria,maria@example.com\n"
                        ),
                    'target' => 'leads',
                    'delimiter' => ',',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $import = Import::query()
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'imports.preview',
                $import->id
            )
        );

        $preview = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/imports/{$import->id}/preview"
            );

        $preview->assertOk();

        $preview->assertSee(
            'Maria',
            false
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'import.uploaded',
            ]
        );
    }

    public function test_import_can_be_dispatched(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$tenant, $user] =
            $this->environment(
                'imports-dispatch'
            );

        $import = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'leads.csv',
                    "name\nMaria\n"
                ),
            ',',
            ImportTarget::LEADS
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/imports/{$import->id}/dispatch"
            );

        $response->assertRedirect(
            route(
                'imports.show',
                $import->id
            )
        );

        Queue::assertPushed(
            ProcessImport::class
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'import.dispatched',
            ]
        );
    }

    public function test_other_tenant_import_cannot_be_viewed(): void
    {
        Storage::fake('local');

        [$tenantA, $userA] =
            $this->environment(
                'imports-tenant-a'
            );

        $importA = app(
            ImportUploadService::class
        )->store(
            UploadedFile::fake()
                ->createWithContent(
                    'a.csv',
                    "name\nA\n"
                ),
            ',',
            ImportTarget::LEADS
        );

        [$tenantB, $userB] =
            $this->environment(
                'imports-tenant-b'
            );

        $this
            ->actingAs($userB)
            ->get(
                "http://{$tenantB->slug}.localhost/imports/{$importA->id}"
            )
            ->assertNotFound();
    }

    public function test_show_displays_import_status(): void
    {
        Storage::fake('local');

        [$tenant, $user] =
            $this->environment(
                'imports-show'
            );

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

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/imports/{$import->id}"
            )
            ->assertOk()
            ->assertSee(
                'data.csv',
                false
            );
    }
}
