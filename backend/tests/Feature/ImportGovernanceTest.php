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

class ImportGovernanceTest extends TestCase
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

    public function test_import_feature_exists(): void
    {
        $this->assertSame(
            'imports',
            Feature::IMPORTS->value
        );
    }

    public function test_import_permissions_exist_in_catalog(): void
    {
        $this->assertSame(
            'imports.view',
            PermissionEnum::IMPORTS_VIEW->value
        );

        $this->assertSame(
            'imports.create',
            PermissionEnum::IMPORTS_CREATE->value
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'name' => 'imports.view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'name' => 'imports.create',
            ]
        );
    }

    public function test_import_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'imports-a'
        );

        $tenantB = $this->tenant(
            'imports-b'
        );

        app(TenantCapabilities::class)->set(
            $tenantA,
            Feature::IMPORTS,
            true
        );

        app(TenantCapabilities::class)->set(
            $tenantB,
            Feature::IMPORTS,
            false
        );

        $this->assertTrue(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantA,
                    Feature::IMPORTS
                )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantB,
                    Feature::IMPORTS
                )
        );
    }

    public function test_admin_role_receives_import_permissions(): void
    {
        $tenant = $this->tenant(
            'imports-admin'
        );

        $admin = User::create([
            'name' => 'Import Admin',
            'email' => 'import-admin@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'admin',
        ]);

        app(RolePermissionSync::class)
            ->sync(
                $admin
            );

        $names = $admin
            ->permissions()
            ->pluck('name')
            ->all();

        $this->assertContains(
            'imports.view',
            $names
        );

        $this->assertContains(
            'imports.create',
            $names
        );
    }
}
