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

class ProposalGovernanceTest extends TestCase
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

    public function test_proposals_feature_exists(): void
    {
        $this->assertSame(
            'proposals',
            Feature::PROPOSALS->value
        );
    }

    public function test_proposal_permissions_exist(): void
    {
        foreach ([
            PermissionEnum::PROPOSALS_VIEW,
            PermissionEnum::PROPOSALS_CREATE,
            PermissionEnum::PROPOSALS_UPDATE,
            PermissionEnum::PROPOSALS_DELETE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' => $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_proposal_permissions(): void
    {
        $this->tenant(
            'proposal-admin'
        );

        $admin = User::create([
            'name' => 'Proposal Admin',
            'email' => 'proposal-admin@local',
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
            'proposals.view',
            'proposals.create',
            'proposals.update',
            'proposals.delete',
        ] as $name) {
            $this->assertContains(
                $name,
                $names
            );
        }
    }

    public function test_proposals_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'proposal-feature-a'
        );

        $tenantB = $this->tenant(
            'proposal-feature-b'
        );

        app(TenantCapabilities::class)
            ->set(
                $tenantA,
                Feature::PROPOSALS,
                true
            );

        app(TenantCapabilities::class)
            ->set(
                $tenantB,
                Feature::PROPOSALS,
                false
            );

        $this->assertTrue(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantA,
                    Feature::PROPOSALS
                )
        );

        $this->assertFalse(
            app(TenantCapabilities::class)
                ->enabled(
                    $tenantB,
                    Feature::PROPOSALS
                )
        );
    }
}
