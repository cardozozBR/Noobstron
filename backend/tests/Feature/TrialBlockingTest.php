<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_active_trial_is_blocked(): void
    {
        $tenant = $this->expiredTenant();

        $changed = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-31 12:00:00',
                'UTC'
            )
        );

        $this->assertTrue($changed);

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    public function test_active_trial_is_not_blocked(): void
    {
        $tenant = $this->expiredTenant();

        $changed = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-20 12:00:00',
                'UTC'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_tenant_without_trial_is_not_blocked(): void
    {
        $tenant = $this->tenant();

        $changed = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-31 12:00:00',
                'UTC'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_blocking_is_idempotent(): void
    {
        $tenant = $this->expiredTenant();

        $service = app(
            TrialService::class
        );

        $moment = CarbonImmutable::parse(
            '2026-08-31 12:00:00',
            'UTC'
        );

        $this->assertTrue(
            $service->blockIfExpired(
                $tenant,
                $moment
            )
        );

        $this->assertFalse(
            $service->blockIfExpired(
                $tenant->refresh(),
                $moment
            )
        );

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    public function test_non_active_status_is_preserved(): void
    {
        $tenant = $this->expiredTenant();

        $tenant->forceFill([
            'status' => 'suspended',
        ])->save();

        $changed = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-31 12:00:00',
                'UTC'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'suspended',
            $tenant->refresh()->status
        );
    }

    public function test_blocking_does_not_remove_trial_dates(): void
    {
        $tenant = $this->expiredTenant();

        $startedAt =
            $tenant->trial_started_at;

        $endsAt =
            $tenant->trial_ends_at;

        app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-31 12:00:00',
                'UTC'
            )
        );

        $tenant->refresh();

        $this->assertTrue(
            $tenant->trial_started_at
                ->equalTo($startedAt)
        );

        $this->assertTrue(
            $tenant->trial_ends_at
                ->equalTo($endsAt)
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Trial Blocking Tenant',
            'slug' => uniqid('trial-blocking-', true),
            'status' => 'active',
        ]);
    }

    private function expiredTenant(): Tenant
    {
        return app(
            TrialService::class
        )->start(
            $this->tenant(),
            CarbonImmutable::parse(
                '2026-08-17 12:00:00',
                'UTC'
            )
        );
    }
}