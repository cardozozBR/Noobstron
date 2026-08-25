<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Tenant;
use App\Services\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformTenantTrialExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_extend_tenant_trial(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' => 'trial-admin@example.test',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Trial Extension Tenant',
            'slug' => 'trial-extension-tenant',
            'status' => 'active',
        ]);

        $moment = CarbonImmutable::parse(
            '2026-08-25 12:00:00',
            'UTC'
        );

        app(TrialService::class)->start(
            $tenant,
            $moment
        );

        $beforeEnd = $tenant
            ->refresh()
            ->trial_ends_at
            ->copy();

        $this->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.tenants.trial.extend',
                    $tenant
                ),
                [
                    'days' => 7,
                    'reason' => 'Commercial courtesy.',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.tenants.show',
                    $tenant
                )
            );

        $tenant->refresh();

        $this->assertSame(
            $beforeEnd->addDays(7)->format('Y-m-d H:i:s'),
            $tenant->trial_ends_at->format('Y-m-d H:i:s')
        );

        $log = PlatformAdminAuditLog::query()
            ->where(
                'action',
                'tenant.trial_extended'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $admin->id,
            $log->platform_admin_id
        );

        $this->assertSame(
            $tenant->id,
            $log->tenant_id
        );

        $this->assertSame(
            'Commercial courtesy.',
            $log->reason
        );

        $this->assertNotNull(
            $log->before_state['trial_ends_at']
        );

        $this->assertNotNull(
            $log->after_state['trial_ends_at']
        );
    }

    public function test_trial_extension_days_are_validated(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' => 'validation-admin@example.test',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Validation Tenant',
            'slug' => 'validation-tenant',
            'status' => 'active',
        ]);

        app(TrialService::class)->start($tenant);

        $this->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.tenants.trial.extend',
                    $tenant
                ),
                ['days' => 91]
            )
            ->assertSessionHasErrors('days');
    }
}
