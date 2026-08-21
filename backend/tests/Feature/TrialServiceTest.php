<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TrialServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_can_be_started_with_default_period(): void
    {
        $tenant = $this->tenant();

        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $tenant = app(
            TrialService::class
        )->start(
            $tenant,
            $start
        );

        $this->assertTrue(
            $tenant->trial_started_at->equalTo(
                $start
            )
        );

        $this->assertTrue(
            $tenant->trial_ends_at->equalTo(
                $start->addDays(14)
            )
        );
    }

    public function test_trial_can_use_custom_duration(): void
    {
        $tenant = $this->tenant();

        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $tenant = app(
            TrialService::class
        )->start(
            $tenant,
            $start,
            30
        );

        $this->assertTrue(
            $tenant->trial_ends_at->equalTo(
                $start->addDays(30)
            )
        );
    }

    public function test_trial_cannot_be_started_twice(): void
    {
        $tenant = $this->tenant();

        $service = app(
            TrialService::class
        );

        $service->start(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-17 12:00:00',
                'UTC'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->start(
            $tenant->refresh()
        );
    }

    public function test_tenant_without_trial_is_not_started(): void
    {
        $this->assertSame(
            TrialService::STATUS_NOT_STARTED,
            app(
                TrialService::class
            )->status(
                $this->tenant()
            )
        );
    }

    public function test_trial_is_active_inside_period(): void
    {
        $tenant = $this->startedTenant();

        $this->assertSame(
            TrialService::STATUS_ACTIVE,
            app(
                TrialService::class
            )->status(
                $tenant,
                CarbonImmutable::parse(
                    '2026-08-20 12:00:00',
                    'UTC'
                )
            )
        );
    }

    public function test_trial_is_expired_at_end(): void
    {
        $tenant = $this->startedTenant();

        $this->assertSame(
            TrialService::STATUS_EXPIRED,
            app(
                TrialService::class
            )->status(
                $tenant,
                CarbonImmutable::parse(
                    '2026-08-31 12:00:00',
                    'UTC'
                )
            )
        );

        $this->assertTrue(
            app(
                TrialService::class
            )->isExpired(
                $tenant,
                CarbonImmutable::parse(
                    '2026-08-31 12:00:00',
                    'UTC'
                )
            )
        );
    }

    public function test_future_trial_is_not_started(): void
    {
        $tenant = $this->startedTenant();

        $this->assertSame(
            TrialService::STATUS_NOT_STARTED,
            app(
                TrialService::class
            )->status(
                $tenant,
                CarbonImmutable::parse(
                    '2026-08-17 11:59:59',
                    'UTC'
                )
            )
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Trial Service Tenant',
            'slug' => uniqid('trial-service-', true),
            'status' => 'active',
        ]);
    }

    private function startedTenant(): Tenant
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