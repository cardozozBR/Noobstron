<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubscriptionBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_be_marked_as_paid(): void
    {
        $subscription = $this->subscription();

        $result = app(
            SubscriptionBillingService::class
        )->markPaid(
            $subscription,
            'mercado_pago',
            'mp-123',
            'pix',
            CarbonImmutable::parse(
                '2026-08-20 12:00:00 UTC'
            ),
        );

        $this->assertSame(
            'mercado_pago',
            $result->payment_provider
        );

        $this->assertSame(
            'mp-123',
            $result->external_reference
        );

        $this->assertSame(
            'pix',
            $result->payment_method
        );

        $this->assertSame(
            '2026-08-20 12:00:00',
            $result->paid_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertTrue(
            app(
                SubscriptionBillingService::class
            )->isPaid($result)
        );
    }

    public function test_blank_provider_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionBillingService::class
        )->markPaid(
            $this->subscription(),
            '',
            'mp-123',
        );
    }

    public function test_blank_external_reference_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionBillingService::class
        )->markPaid(
            $this->subscription(),
            'mercado_pago',
            '',
        );
    }

    private function subscription(): Subscription
    {
        $tenant = Tenant::query()->create([
            'name' => 'Billing Service Tenant',
            'slug' => uniqid(
                'billing-service-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'billing-service-plan-',
                false
            ),
            'name' => 'Billing Service Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
        ]);
    }
}