<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_can_persist_billing_fields(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Billing Tenant',
            'slug' => uniqid('billing-', true),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid('billing-plan-', false),
            'name' => 'Billing Plan',
            'active' => true,
        ]);

        $paidAt = CarbonImmutable::parse(
            '2026-08-20 12:00:00 UTC'
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'mp-subscription-123',
            'payment_method' => 'pix',
            'paid_at' => $paidAt,
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
        ]);

        $subscription->refresh();

        $this->assertSame(
            'mercado_pago',
            $subscription->payment_provider
        );

        $this->assertSame(
            'mp-subscription-123',
            $subscription->external_reference
        );

        $this->assertSame(
            'pix',
            $subscription->payment_method
        );

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $subscription->paid_at
        );

        $this->assertSame(
            '2026-08-20 12:00:00',
            $subscription->paid_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }
}
