<?php

namespace Tests\Feature;

use App\Contracts\SubscriptionPaymentProvider;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionPaymentProviderRegistry;
use App\Support\SubscriptionCheckoutResult;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubscriptionCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_provider_can_start_checkout(): void
    {
        $registry = app(
            SubscriptionPaymentProviderRegistry::class
        );

        $registry->register(
            $this->provider(
                SubscriptionCheckoutResult::success(
                    'mp-sub-123',
                    'https://checkout.example/sub-123',
                )
            )
        );

        $subscription = $this->subscription();

        $result = app(
            SubscriptionCheckoutService::class
        )->checkout(
            $subscription,
            'test',
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            'mp-sub-123',
            $result->externalReference
        );

        $this->assertSame(
            'https://checkout.example/sub-123',
            $result->checkoutUrl
        );

        $subscription->refresh();

        $this->assertSame(
            'test',
            $subscription->payment_provider
        );

        $this->assertSame(
            'mp-sub-123',
            $subscription->external_reference
        );

        $this->assertNull(
            $subscription->paid_at
        );
    }

    public function test_registry_knows_registered_provider(): void
    {
        $registry = app(
            SubscriptionPaymentProviderRegistry::class
        );

        $registry->register(
            $this->provider(
                SubscriptionCheckoutResult::success(
                    'ref',
                    'https://checkout.example',
                )
            )
        );

        $this->assertTrue(
            $registry->has('test')
        );

        $this->assertTrue(
            $registry->has(' TEST ')
        );
    }

    public function test_unknown_provider_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionCheckoutService::class
        )->checkout(
            $this->subscription(),
            'missing',
        );
    }

    public function test_cancelled_subscription_cannot_start_checkout(): void
    {
        $subscription = $this->subscription();

        $subscription->update([
            'status' => 'cancelled',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionCheckoutService::class
        )->checkout(
            $subscription->refresh(),
            'test',
        );
    }

    public function test_suspended_subscription_cannot_start_checkout(): void
    {
        $subscription = $this->subscription();

        $subscription->update([
            'status' => 'suspended',
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            SubscriptionCheckoutService::class
        )->checkout(
            $subscription->refresh(),
            'test',
        );
    }

    private function provider(
        SubscriptionCheckoutResult $result
    ): SubscriptionPaymentProvider {
        return new class($result)
            implements SubscriptionPaymentProvider {
            public function __construct(
                private SubscriptionCheckoutResult $result
            ) {
            }

            public function code(): string
            {
                return 'test';
            }

            public function checkout(
                Subscription $subscription
            ): SubscriptionCheckoutResult {
                return $this->result;
            }
        };
    }

    private function subscription(): Subscription
    {
        $tenant = Tenant::query()->create([
            'name' => 'Checkout Tenant',
            'slug' => uniqid(
                'subscription-checkout-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'subscription-plan-',
                false
            ),
            'name' => 'Subscription Checkout Plan',
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