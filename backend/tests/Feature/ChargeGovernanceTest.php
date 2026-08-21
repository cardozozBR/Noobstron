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

class ChargeGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_charges_feature_exists(): void
    {
        $this->assertSame(
            'charges',
            Feature::CHARGES->value
        );
    }

    public function test_charges_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::CHARGES_VIEW,
            PermissionEnum::CHARGES_CREATE,
            PermissionEnum::CHARGES_UPDATE,
            PermissionEnum::CHARGES_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' =>
                        $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_charges_permissions(): void
    {
        $tenant = $this->tenant(
            'charges-admin'
        );

        $admin = User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Charges Admin',
            'email' =>
                'charges-admin@local',
            'password' =>
                Hash::make(
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
            'charges.view',
            'charges.create',
            'charges.update',
            'charges.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_charges_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'charges-feature-a'
        );

        $tenantB = $this->tenant(
            'charges-feature-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::CHARGES,
            true
        );

        $capabilities->set(
            $tenantB,
            Feature::CHARGES,
            false
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::CHARGES
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::CHARGES
            )
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}