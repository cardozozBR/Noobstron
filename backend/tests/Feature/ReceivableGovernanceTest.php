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

class ReceivableGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_receivables_feature_exists(): void
    {
        $this->assertSame(
            'receivables',
            Feature::RECEIVABLES->value
        );
    }

    public function test_receivables_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::RECEIVABLES_VIEW,
            PermissionEnum::RECEIVABLES_CREATE,
            PermissionEnum::RECEIVABLES_UPDATE,
            PermissionEnum::RECEIVABLES_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' => $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_receivables_permissions(): void
    {
        $tenant = $this->tenant(
            'receivables-admin'
        );

        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Receivables Admin',
            'email' => 'receivables-admin@local',
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
            'receivables.view',
            'receivables.create',
            'receivables.update',
            'receivables.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_receivables_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'receivables-feature-a'
        );

        $tenantB = $this->tenant(
            'receivables-feature-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::RECEIVABLES,
            true
        );

        $capabilities->set(
            $tenantB,
            Feature::RECEIVABLES,
            false
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::RECEIVABLES
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::RECEIVABLES
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
