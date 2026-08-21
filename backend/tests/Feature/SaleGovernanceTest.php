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

class SaleGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_sales_feature_exists(): void
    {
        $this->assertSame(
            'sales',
            Feature::SALES->value
        );
    }

    public function test_sales_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::SALES_VIEW,
            PermissionEnum::SALES_CREATE,
            PermissionEnum::SALES_UPDATE,
            PermissionEnum::SALES_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' => $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_sales_permissions(): void
    {
        $tenant = $this->tenant(
            'sales-admin'
        );

        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sales Admin',
            'email' => 'sales-admin@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'admin',
        ]);

        app(RolePermissionSync::class)
            ->sync($admin);

        $names = $admin
            ->permissions()
            ->pluck('name')
            ->all();

        foreach ([
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_sales_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'sales-feature-a'
        );

        $tenantB = $this->tenant(
            'sales-feature-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::SALES,
            true
        );

        $capabilities->set(
            $tenantB,
            Feature::SALES,
            false
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::SALES
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::SALES
            )
        );
    }

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
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
}
