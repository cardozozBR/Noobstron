<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

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

    public function test_catalog_feature_exists(): void
    {
        $this->assertSame(
            'catalog',
            Feature::CATALOG->value
        );
    }

    public function test_catalog_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::CATALOG_VIEW,
            PermissionEnum::CATALOG_CREATE,
            PermissionEnum::CATALOG_UPDATE,
            PermissionEnum::CATALOG_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' => $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_catalog_permissions(): void
    {
        $this->tenant(
            'catalog-admin'
        );

        $admin = User::create([
            'name' => 'Admin Catalog',
            'email' => 'catalog-admin@local',
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
            'catalog.view',
            'catalog.create',
            'catalog.update',
            'catalog.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_catalog_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'catalog-feature-a'
        );

        $tenantB = $this->tenant(
            'catalog-feature-b'
        );

        app(TenantCapabilities::class)
            ->set(
                $tenantA,
                Feature::CATALOG,
                true
            );

        app(TenantCapabilities::class)
            ->set(
                $tenantB,
                Feature::CATALOG,
                false
            );

        $this->assertTrue(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantA,
                    Feature::CATALOG
                )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantB,
                    Feature::CATALOG
                )
        );
    }
}
