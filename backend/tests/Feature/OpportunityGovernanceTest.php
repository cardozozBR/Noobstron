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

class OpportunityGovernanceTest extends TestCase
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
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    public function test_opportunity_feature_exists(): void
    {
        $this->assertSame(
            'opportunities',
            Feature::OPPORTUNITIES->value
        );
    }

    public function test_opportunity_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::OPPORTUNITIES_VIEW,
            PermissionEnum::OPPORTUNITIES_CREATE,
            PermissionEnum::OPPORTUNITIES_UPDATE,
            PermissionEnum::OPPORTUNITIES_DELETE,
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

    public function test_admin_receives_opportunity_permissions(): void
    {
        $this->tenant(
            'opportunity-admin'
        );

        $admin = User::create([
            'name' => 'Admin',
            'email' =>
                'opportunity-admin@local',
            'password' =>
                Hash::make(
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
            'opportunities.view',
            'opportunities.create',
            'opportunities.update',
            'opportunities.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_opportunity_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'opportunity-feature-a'
        );

        $tenantB = $this->tenant(
            'opportunity-feature-b'
        );

        app(TenantCapabilities::class)
            ->set(
                $tenantA,
                Feature::OPPORTUNITIES,
                true
            );

        app(TenantCapabilities::class)
            ->set(
                $tenantB,
                Feature::OPPORTUNITIES,
                false
            );

        $this->assertTrue(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantA,
                    Feature::OPPORTUNITIES
                )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantB,
                    Feature::OPPORTUNITIES
                )
        );
    }
}
