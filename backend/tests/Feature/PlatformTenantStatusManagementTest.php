<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformTenantStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_suspend_tenant(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();

        $this->actingAs($admin, 'platform')
            ->post(
                route('platform.tenants.suspend', $tenant),
                ['reason' => 'Administrative suspension.']
            )
            ->assertRedirect(
                route('platform.tenants.show', $tenant)
            );

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );

        $log = PlatformAdminAuditLog::query()
            ->where('action', 'tenant.suspended')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($admin->id, $log->platform_admin_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame(
            ['status' => 'active'],
            $log->before_state
        );
        $this->assertSame(
            ['status' => 'blocked'],
            $log->after_state
        );
        $this->assertSame(
            'Administrative suspension.',
            $log->reason
        );
    }

    public function test_guest_cannot_suspend_tenant(): void
{
    $tenant = $this->tenant();

    $this->post(
        route('platform.tenants.suspend', $tenant),
        ['reason' => 'Unauthorized suspension.']
    )
        ->assertRedirect(
            route('platform.login')
        );

    $this->assertSame(
        'active',
        $tenant->refresh()->status
    );

    $this->assertDatabaseMissing(
        'platform_admin_audit_logs',
        [
            'action' => 'tenant.suspended',
            'tenant_id' => $tenant->id,
        ]
    );
}

public function test_suspended_tenant_workspace_is_no_longer_resolved(): void
{
    $admin = $this->admin();
    $tenant = $this->tenant();

    $this->actingAs($admin, 'platform')
        ->post(
            route('platform.tenants.suspend', $tenant),
            ['reason' => 'Security suspension.']
        )
        ->assertRedirect(
            route('platform.tenants.show', $tenant)
        );

    $this->get(
        'http://'.$tenant->slug.'.localhost/'
    )
        ->assertNotFound();
}

public function test_status_changes_do_not_modify_subscription(): void
{
    $admin = $this->admin();
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'name' => 'Status Management Plan',
        'code' => 'status-management-plan',
        'slug' => 'status-management-plan',
        'active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($admin, 'platform')
        ->post(
            route('platform.tenants.suspend', $tenant),
            ['reason' => 'Administrative suspension.']
        )
        ->assertRedirect(
            route('platform.tenants.show', $tenant)
        );

    $this->assertSame(
        SubscriptionStatus::ACTIVE,
        $subscription->refresh()->status
    );

    $this->actingAs($admin, 'platform')
        ->post(
            route('platform.tenants.reactivate', $tenant),
            ['reason' => 'Administrative reactivation.']
        )
        ->assertRedirect(
            route('platform.tenants.show', $tenant)
        );

    $this->assertSame(
        SubscriptionStatus::ACTIVE,
        $subscription->refresh()->status
    );
}

    public function test_platform_admin_can_reactivate_tenant(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant('blocked');

        $this->actingAs($admin, 'platform')
            ->post(
                route('platform.tenants.reactivate', $tenant),
                ['reason' => 'Administrative reactivation.']
            )
            ->assertRedirect(
                route('platform.tenants.show', $tenant)
            );

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );

        $log = PlatformAdminAuditLog::query()
            ->where('action', 'tenant.reactivated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            ['status' => 'blocked'],
            $log->before_state
        );
        $this->assertSame(
            ['status' => 'active'],
            $log->after_state
        );
        $this->assertSame(
            'Administrative reactivation.',
            $log->reason
        );
    }

    public function test_reason_is_required_for_status_changes(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();

        $this->actingAs($admin, 'platform')
            ->post(
                route('platform.tenants.suspend', $tenant)
            )
            ->assertSessionHasErrors('reason');

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_blocked_tenant_cannot_be_suspended_again(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant('blocked');

        $this->actingAs($admin, 'platform')
            ->post(
                route('platform.tenants.suspend', $tenant),
                ['reason' => 'Duplicate suspension.']
            )
            ->assertSessionHasErrors('status');

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );

        $this->assertDatabaseMissing(
            'platform_admin_audit_logs',
            [
                'action' => 'tenant.suspended',
                'tenant_id' => $tenant->id,
            ]
        );
    }

    public function test_active_tenant_cannot_be_reactivated(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant('active');

        $this->actingAs($admin, 'platform')
            ->post(
                route('platform.tenants.reactivate', $tenant),
                ['reason' => 'Invalid reactivation.']
            )
            ->assertSessionHasErrors('status');

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );

        $this->assertDatabaseMissing(
            'platform_admin_audit_logs',
            [
                'action' => 'tenant.reactivated',
                'tenant_id' => $tenant->id,
            ]
        );
    }

    public function test_active_tenant_shows_suspend_action_only(): void
    {
        $tenant = $this->tenant('active');

        $this->actingAs(
            $this->admin(),
            'platform'
        )
            ->get(
                route(
                    'platform.tenants.show',
                    $tenant
                )
            )
            ->assertOk()
            ->assertSee('Suspender tenant')
            ->assertSee('Motivo da suspensão')
            ->assertDontSee('Reativar tenant');
    }

    public function test_blocked_tenant_shows_reactivate_action_only(): void
    {
        $tenant = $this->tenant('blocked');

        $this->actingAs(
            $this->admin(),
            'platform'
        )
            ->get(
                route(
                    'platform.tenants.show',
                    $tenant
                )
            )
            ->assertOk()
            ->assertSee('Reativar tenant')
            ->assertSee('Motivo da reativação')
            ->assertDontSee('Suspender tenant');
    }

    private function admin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' => 'status-admin@example.test',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);
    }

    private function tenant(string $status = 'active'): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Status Management Tenant',
            'slug' => 'status-management-tenant',
            'status' => $status,
        ]);
    }
}
