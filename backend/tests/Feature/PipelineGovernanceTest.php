<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PipelineGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

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

    public function test_pipeline_feature_exists(): void
    {
        $this->assertSame(
            'pipelines',
            Feature::PIPELINES->value
        );
    }

    public function test_pipeline_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::PIPELINES_VIEW,
            PermissionEnum::PIPELINES_CREATE,
            PermissionEnum::PIPELINES_UPDATE,
            PermissionEnum::PIPELINES_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' => $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_pipeline_permissions(): void
    {
        $this->tenant('pipeline-admin');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'pipeline-admin@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'admin',
        ]);

        app(RolePermissionSync::class)
            ->sync($admin);

        $names = $admin->permissions()
            ->pluck('name')
            ->all();

        foreach ([
            'pipelines.view',
            'pipelines.create',
            'pipelines.update',
            'pipelines.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_pipeline_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant('pipeline-feature-a');
        $tenantB = $this->tenant('pipeline-feature-b');

        app(TenantCapabilities::class)->set(
            $tenantA,
            Feature::PIPELINES,
            true
        );

        app(TenantCapabilities::class)->set(
            $tenantB,
            Feature::PIPELINES,
            false
        );

        $this->assertTrue(
            app(TenantCapabilities::class)->enabled(
                $tenantA,
                Feature::PIPELINES
            )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)->enabled(
                $tenantB,
                Feature::PIPELINES
            )
        );
    }
}
