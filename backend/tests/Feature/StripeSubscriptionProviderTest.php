<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StripeSubscriptionProvider;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeSubscriptionProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_configured_stripe_price_id(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        config()->set(
            'services.stripe.return_url',
            'http://tenant.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/checkout/sessions' =>
                Http::response([
                    'id' => 'cs_test_123',
                    'url' =>
                        'https://checkout.stripe.test/c/pay/test',
                ], 200),
        ]);

        $subscription = $this->subscription(
            'price_test_123'
        );

        app(TenantContext::class)->set(
            $subscription->tenant
        );

        $result = app(
            StripeSubscriptionProvider::class
        )->checkout(
            $subscription
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            'cs_test_123',
            $result->externalReference
        );

        $this->assertSame(
            'https://checkout.stripe.test/c/pay/test',
            $result->checkoutUrl
        );

        Http::assertSent(
            function ($request): bool {
                return
                    $request->url()
                    === 'https://api.stripe.test/v1/checkout/sessions'
                    && $request[
                        'mode'
                    ] === 'subscription'
                    && $request[
                        'line_items[0][price]'
                    ] === 'price_test_123'
                    && $request[
                        'line_items[0][quantity]'
                    ] === 1;
            }
        );
    }

    public function test_checkout_fails_when_stripe_price_id_is_missing(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        config()->set(
            'services.stripe.return_url',
            'http://tenant.test'
        );

        Http::fake();

        $subscription = $this->subscription(
            null
        );

        app(TenantContext::class)->set(
            $subscription->tenant
        );

        $result = app(
            StripeSubscriptionProvider::class
        )->checkout(
            $subscription
        );

        $this->assertFalse(
            $result->successful
        );

        $this->assertSame(
            'Stripe price is not configured for this plan.',
            $result->failureReason
        );

        Http::assertNothingSent();
    }

    private function subscription(
        ?string $stripePriceId
    ): Subscription {
       $tenant = Tenant::query()->create([
    'name' => 'Stripe Provider Tenant',
    'slug' => uniqid(
        'stripe-provider-',
        true
    ),
    'status' => 'active',
    'currency' => 'BRL',
]);

app(TenantContext::class)->set(
    $tenant
);

User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'email' => uniqid(
                'stripe-admin-',
                true
            ) . '@example.com',
            'password' => bcrypt(
                'password'
            ),
            'role' => 'admin',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'stripe-provider-plan-',
                false
            ),
            'name' => 'Stripe Provider Plan',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'stripe_price_id' =>
                $stripePriceId,
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