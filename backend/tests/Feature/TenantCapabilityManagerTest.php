<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantCapabilityManager;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantCapabilityManagerTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug = 'tenant-capability'
    ): Tenant {
        return Tenant::create([
            'name' => 'Tenant Capability',
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function user(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Capability Admin',
            'email' => 'capability-admin@'
                . $tenant->slug
                . '.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'admin',
        ]);
    }

    public function test_feature_change_is_audited(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        app(TenantCapabilityManager::class)->setFeature(
            $tenant,
            Feature::AUDIT,
            true,
            $actor
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' => 'tenant.feature.updated',
        ]);
    }

    public function test_idempotent_feature_change_is_not_audited_twice(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        $manager = app(TenantCapabilityManager::class);

        $manager->setFeature(
            $tenant,
            Feature::AUDIT,
            true,
            $actor
        );

        $manager->setFeature(
            $tenant,
            Feature::AUDIT,
            true,
            $actor
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->where(
                    'action',
                    'tenant.feature.updated'
                )
                ->count()
        );
    }

    public function test_limit_change_is_audited(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        app(TenantCapabilityManager::class)->setLimit(
            $tenant,
            Feature::USERS,
            25,
            $actor
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' => 'tenant.feature.limit.updated',
        ]);
    }

    public function test_idempotent_limit_change_is_not_audited_twice(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        $manager = app(TenantCapabilityManager::class);

        $manager->setLimit(
            $tenant,
            Feature::USERS,
            25,
            $actor
        );

        $manager->setLimit(
            $tenant,
            Feature::USERS,
            25,
            $actor
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->where(
                    'action',
                    'tenant.feature.limit.updated'
                )
                ->count()
        );
    }

    public function test_profile_change_is_audited(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        app(TenantCapabilityManager::class)->applyProfile(
            $tenant,
            [
                [
                    'feature' => Feature::USERS,
                    'enabled' => true,
                    'limit' => 10,
                ],
                [
                    'feature' => Feature::AUDIT,
                    'enabled' => true,
                    'limit' => null,
                ],
            ],
            $actor
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' =>
                'tenant.capability_profile.applied',
        ]);
    }

    public function test_idempotent_profile_is_not_audited_twice(): void
    {
        $tenant = $this->tenant();
        $actor = $this->user($tenant);

        $manager = app(TenantCapabilityManager::class);

        $profile = [
            [
                'feature' => Feature::USERS,
                'enabled' => true,
                'limit' => 10,
            ],
            [
                'feature' => Feature::AUDIT,
                'enabled' => true,
                'limit' => null,
            ],
        ];

        $manager->applyProfile(
            $tenant,
            $profile,
            $actor
        );

        $manager->applyProfile(
            $tenant,
            $profile,
            $actor
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->where(
                    'action',
                    'tenant.capability_profile.applied'
                )
                ->count()
        );
    }

    public function test_audit_is_written_to_target_tenant(): void
    {
        $tenantA = $this->tenant('capability-a');
        $tenantB = $this->tenant('capability-b');

        $actor = $this->user($tenantA);

        app(TenantCapabilityManager::class)->setFeature(
            $tenantB,
            Feature::BRANDING,
            true,
            $actor
        );

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenantB->id,
            'user_id' => $actor->id,
            'action' => 'tenant.feature.updated',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => 'tenant.feature.updated',
        ]);
    }
}