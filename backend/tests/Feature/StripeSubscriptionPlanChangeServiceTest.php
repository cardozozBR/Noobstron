<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\StripeSubscriptionPlanChangeService;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeSubscriptionPlanChangeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_subscription_can_change_from_start_to_pro(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_test_123' =>
                Http::response([
                    'id' => 'sub_test_123',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_123',
                            ],
                        ],
                    ],
                ], 200),

            'https://api.stripe.test/v1/subscriptions/sub_test_123' =>
                Http::sequence()
                    ->push([
                        'id' => 'sub_test_123',
                        'items' => [
                            'data' => [
                                [
                                    'id' => 'si_test_123',
                                ],
                            ],
                        ],
                    ], 200)
                    ->push([
                        'id' => 'sub_test_123',
                        'status' => 'active',
                    ], 200),
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Stripe Plan Change Tenant',
            'slug' => uniqid(
                'stripe-plan-change-',
                true
            ),
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $start = Plan::query()->create([
            'code' => 'start-test',
            'name' => 'Start',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $start->id,
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'stripe_price_id' => 'price_start_123',
        ]);

        $pro = Plan::query()->create([
            'code' => 'pro-test',
            'name' => 'Pro',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $pro->id,
            'currency' => 'BRL',
            'amount_minor' => 24900,
            'stripe_price_id' => 'price_pro_123',
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $start->id,
            'status' => 'active',
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_test_123',
            'paid_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 12:00:00 UTC'
                ),
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
        ]);

        $result = app(
            StripeSubscriptionPlanChangeService::class
        )->change(
            $subscription,
            $pro
        );

        $this->assertSame(
            $pro->id,
            $result->plan_id
        );

        Http::assertSent(
            function ($request): bool {
                if (
                    $request->method() !== 'POST'
                    || $request->url()
                    !== 'https://api.stripe.test/v1/subscriptions/sub_test_123'
                ) {
                    return false;
                }

                return
                    $request[
                        'items[0][id]'
                    ] === 'si_test_123'
                    && $request[
                        'items[0][price]'
                    ] === 'price_pro_123'
                    && $request[
                        'proration_behavior'
                    ] === 'create_prorations';
            }
        );
    }

    public function test_local_plan_is_not_changed_when_stripe_rejects_change(): void
{
    config()->set(
        'services.stripe.secret_key',
        'TEST_STRIPE_SECRET'
    );

    config()->set(
        'services.stripe.base_url',
        'https://api.stripe.test'
    );

    Http::fake([
        'https://api.stripe.test/v1/subscriptions/sub_reject_123' =>
            Http::sequence()
                ->push([
                    'id' => 'sub_reject_123',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_reject_123',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'error' => [
                        'message' =>
                            'Stripe rejected plan change.',
                    ],
                ], 400),
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Stripe Rejected Change Tenant',
        'slug' => uniqid(
            'stripe-rejected-change-',
            true
        ),
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    app(TenantContext::class)->set(
        $tenant
    );

    $start = Plan::query()->create([
        'code' => 'start-rejected-test',
        'name' => 'Start',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $start->id,
        'currency' => 'BRL',
        'amount_minor' => 9900,
        'stripe_price_id' => 'price_start_rejected',
    ]);

    $pro = Plan::query()->create([
        'code' => 'pro-rejected-test',
        'name' => 'Pro',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $pro->id,
        'currency' => 'BRL',
        'amount_minor' => 24900,
        'stripe_price_id' => 'price_pro_rejected',
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $start->id,
        'status' => 'active',
        'payment_provider' => 'stripe',
        'external_reference' => 'sub_reject_123',
        'paid_at' =>
            CarbonImmutable::parse(
                '2026-08-20 12:00:00 UTC'
            ),
        'current_period_start' =>
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        'current_period_end' =>
            CarbonImmutable::parse(
                '2026-09-20 00:00:00 UTC'
            ),
    ]);

    try {
        app(
            StripeSubscriptionPlanChangeService::class
        )->change(
            $subscription,
            $pro
        );

        $this->fail(
            'Expected Stripe plan change to fail.'
        );
    } catch (\RuntimeException $exception) {
        $this->assertSame(
            'Stripe rejected subscription plan change.',
            $exception->getMessage()
        );
    }

    $this->assertSame(
        $start->id,
        $subscription->refresh()->plan_id
    );
}

public function test_change_fails_when_target_plan_has_no_stripe_price(): void
{
    config()->set(
        'services.stripe.secret_key',
        'TEST_STRIPE_SECRET'
    );

    config()->set(
        'services.stripe.base_url',
        'https://api.stripe.test'
    );

    Http::fake();

    $tenant = Tenant::query()->create([
        'name' => 'Missing Stripe Price Tenant',
        'slug' => uniqid(
            'missing-stripe-price-',
            true
        ),
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    app(TenantContext::class)->set(
        $tenant
    );

    $start = Plan::query()->create([
        'code' => 'start-missing-price-test',
        'name' => 'Start',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $start->id,
        'currency' => 'BRL',
        'amount_minor' => 9900,
        'stripe_price_id' => 'price_start_test',
    ]);

    $pro = Plan::query()->create([
        'code' => 'pro-missing-price-test',
        'name' => 'Pro',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $pro->id,
        'currency' => 'BRL',
        'amount_minor' => 24900,
        'stripe_price_id' => null,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $start->id,
        'status' => 'active',
        'payment_provider' => 'stripe',
        'external_reference' => 'sub_missing_price_123',
        'paid_at' =>
            CarbonImmutable::parse(
                '2026-08-20 12:00:00 UTC'
            ),
        'current_period_start' =>
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        'current_period_end' =>
            CarbonImmutable::parse(
                '2026-09-20 00:00:00 UTC'
            ),
    ]);

    $this->expectException(
        \RuntimeException::class
    );

    $this->expectExceptionMessage(
        'Stripe price is not configured for target plan.'
    );

    try {
        app(
            StripeSubscriptionPlanChangeService::class
        )->change(
            $subscription,
            $pro
        );
    } finally {
        Http::assertNothingSent();

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );
    }
}

public function test_non_stripe_subscription_cannot_use_stripe_plan_change(): void
{
    config()->set(
        'services.stripe.secret_key',
        'TEST_STRIPE_SECRET'
    );

    config()->set(
        'services.stripe.base_url',
        'https://api.stripe.test'
    );

    Http::fake();

    $tenant = Tenant::query()->create([
        'name' => 'Non Stripe Tenant',
        'slug' => uniqid(
            'non-stripe-plan-change-',
            true
        ),
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    app(TenantContext::class)->set(
        $tenant
    );

    $start = Plan::query()->create([
        'code' => 'start-non-stripe-test',
        'name' => 'Start',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $start->id,
        'currency' => 'BRL',
        'amount_minor' => 9900,
        'stripe_price_id' => 'price_start_test',
    ]);

    $pro = Plan::query()->create([
        'code' => 'pro-non-stripe-test',
        'name' => 'Pro',
        'active' => true,
    ]);

    PlanPrice::query()->create([
        'plan_id' => $pro->id,
        'currency' => 'BRL',
        'amount_minor' => 24900,
        'stripe_price_id' => 'price_pro_test',
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $start->id,
        'status' => 'active',
        'payment_provider' => 'mercado_pago',
        'external_reference' => 'mp_subscription_123',
        'paid_at' =>
            CarbonImmutable::parse(
                '2026-08-20 12:00:00 UTC'
            ),
        'current_period_start' =>
            CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
        'current_period_end' =>
            CarbonImmutable::parse(
                '2026-09-20 00:00:00 UTC'
            ),
    ]);

    $this->expectException(
        \RuntimeException::class
    );

    $this->expectExceptionMessage(
        'Subscription is not managed by Stripe.'
    );

    try {
        app(
            StripeSubscriptionPlanChangeService::class
        )->change(
            $subscription,
            $pro
        );
    } finally {
        Http::assertNothingSent();

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );
    }
}
}