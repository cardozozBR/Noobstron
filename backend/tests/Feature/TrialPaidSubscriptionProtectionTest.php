<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionBillingService;
use App\Services\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialPaidSubscriptionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_trial_without_payment_blocks_tenant(): void
    {
        $tenant = $this->tenantWithTrial(
            CarbonImmutable::parse(
                '2026-08-01 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-08-15 00:00:00 UTC'
            ),
        );

        $blocked = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        );

        $this->assertTrue($blocked);

        $this->assertSame(
            'blocked',
            $tenant->refresh()->status
        );
    }

    public function test_expired_trial_with_paid_subscription_does_not_block_tenant(): void
    {
        $tenant = $this->tenantWithTrial(
            CarbonImmutable::parse(
                '2026-08-01 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-08-15 00:00:00 UTC'
            ),
        );

        $subscription = $this->subscription($tenant);

        app(
            SubscriptionBillingService::class
        )->markPaid(
            $subscription,
            'mercado_pago',
            'mp-paid-123',
            'pix',
            CarbonImmutable::parse(
                '2026-08-10 12:00:00 UTC'
            ),
        );

        $blocked = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        );

        $this->assertFalse($blocked);

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    public function test_expired_trial_with_paid_stripe_subscription_does_not_block_tenant(): void
{
    $tenant = $this->tenantWithTrial(
        CarbonImmutable::parse(
            '2026-08-01 00:00:00 UTC'
        ),
        CarbonImmutable::parse(
            '2026-08-15 00:00:00 UTC'
        ),
    );

    $subscription = $this->subscription($tenant);

    app(
        SubscriptionBillingService::class
    )->markPaid(
        $subscription,
        'stripe',
        'sub_paid_123',
        'card',
        CarbonImmutable::parse(
            '2026-08-10 12:00:00 UTC'
        ),
    );

    $blocked = app(
        TrialService::class
    )->blockIfExpired(
        $tenant,
        CarbonImmutable::parse(
            '2026-08-20 00:00:00 UTC'
        ),
    );

    $this->assertFalse($blocked);

    $this->assertSame(
        'active',
        $tenant->refresh()->status
    );
}

    public function test_active_trial_does_not_block_tenant(): void
    {
        $tenant = $this->tenantWithTrial(
            CarbonImmutable::parse(
                '2026-08-15 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-08-29 00:00:00 UTC'
            ),
        );

        $blocked = app(
            TrialService::class
        )->blockIfExpired(
            $tenant,
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        );

        $this->assertFalse($blocked);

        $this->assertSame(
            'active',
            $tenant->refresh()->status
        );
    }

    private function tenantWithTrial(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): Tenant {
        return Tenant::query()->create([
            'name' => 'Trial Billing Tenant',
            'slug' => uniqid(
                'trial-billing-',
                true
            ),
            'status' => 'active',
            'trial_started_at' => $startsAt,
            'trial_ends_at' => $endsAt,
        ]);
    }

    private function subscription(
        Tenant $tenant
    ): Subscription {
        $plan = Plan::query()->create([
            'code' => uniqid(
                'trial-billing-plan-',
                false
            ),
            'name' => 'Trial Billing Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-01 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-01 00:00:00 UTC'
                ),
        ]);
    }
}