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

class ActivityGovernanceTest extends TestCase
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

    public function test_activity_feature_exists(): void
    {
        $this->assertSame(
            'activities',
            Feature::ACTIVITIES->value
        );
    }

    public function test_activity_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::ACTIVITIES_VIEW,
            PermissionEnum::ACTIVITIES_CREATE,
            PermissionEnum::ACTIVITIES_UPDATE,
            PermissionEnum::ACTIVITIES_DELETE,
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

    public function test_admin_receives_activity_permissions(): void
    {
        $this->tenant(
            'activity-admin'
        );

        $admin = User::create([
            'name' => 'Admin',
            'email' =>
                'activity-admin@local',
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
            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_activity_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'activity-feature-a'
        );

        $tenantB = $this->tenant(
            'activity-feature-b'
        );

        app(TenantCapabilities::class)
            ->set(
                $tenantA,
                Feature::ACTIVITIES,
                true
            );

        app(TenantCapabilities::class)
            ->set(
                $tenantB,
                Feature::ACTIVITIES,
                false
            );

        $this->assertTrue(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantA,
                    Feature::ACTIVITIES
                )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantB,
                    Feature::ACTIVITIES
                )
        );
    }
}
