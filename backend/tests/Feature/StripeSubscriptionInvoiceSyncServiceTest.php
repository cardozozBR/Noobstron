<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\StripeSubscriptionInvoiceSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class StripeSubscriptionInvoiceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_imports_stripe_invoices_for_subscription(): void
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
            'https://api.stripe.test/v1/subscriptions/sub_sync_123' =>
                Http::response([
                    'id' => 'sub_sync_123',
                    'customer' => 'cus_sync_123',
                ], 200),

            'https://api.stripe.test/v1/invoices*' =>
                Http::response([
                    'data' => [
                        [
                            'id' => 'in_sync_123',
                            'subscription' => 'sub_sync_123',
                            'status' => 'paid',
                            'currency' => 'brl',
                            'amount_due' => 9900,
                            'amount_paid' => 9900,
                            'amount_remaining' => 0,
                            'billing_reason' =>
                                'subscription_cycle',
                            'hosted_invoice_url' =>
                                'https://invoice.test/in_sync_123',
                            'invoice_pdf' =>
                                'https://invoice.test/in_sync_123.pdf',
                            'status_transitions' => [
                                'paid_at' =>
                                    CarbonImmutable::parse(
                                        '2026-09-20 00:01:00 UTC'
                                    )->timestamp,
                            ],
                            'lines' => [
                                'data' => [
                                    [
                                        'period' => [
                                            'start' =>
                                                CarbonImmutable::parse(
                                                    '2026-09-20 00:00:00 UTC'
                                                )->timestamp,
                                            'end' =>
                                                CarbonImmutable::parse(
                                                    '2026-10-20 00:00:00 UTC'
                                                )->timestamp,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $subscription = $this->subscription(
            'stripe',
            'sub_sync_123'
        );

        $synced = app(
            StripeSubscriptionInvoiceSyncService::class
        )->sync(
            $subscription
        );

        $this->assertSame(
            1,
            $synced
        );

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'subscription_id' =>
                    $subscription->id,
                'provider' => 'stripe',
                'external_invoice_id' =>
                    'in_sync_123',
                'status' => 'paid',
                'currency' => 'BRL',
                'amount_due' => 9900,
                'amount_paid' => 9900,
                'amount_remaining' => 0,
                'billing_reason' =>
                    'subscription_cycle',
            ]
        );
    }

    public function test_sync_ignores_invoice_from_other_subscription(): void
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
            'https://api.stripe.test/v1/subscriptions/sub_sync_456' =>
                Http::response([
                    'id' => 'sub_sync_456',
                    'customer' => 'cus_sync_456',
                ], 200),

            'https://api.stripe.test/v1/invoices*' =>
                Http::response([
                    'data' => [
                        [
                            'id' => 'in_other_456',
                            'subscription' =>
                                'sub_other_999',
                            'status' => 'paid',
                            'currency' => 'brl',
                            'amount_due' => 24900,
                            'amount_paid' => 24900,
                            'amount_remaining' => 0,
                        ],
                    ],
                ], 200),
        ]);

        $subscription = $this->subscription(
            'stripe',
            'sub_sync_456'
        );

        $synced = app(
            StripeSubscriptionInvoiceSyncService::class
        )->sync(
            $subscription
        );

        $this->assertSame(
            0,
            $synced
        );

        $this->assertSame(
            0,
            SubscriptionInvoice::query()->count()
        );
    }

    public function test_sync_is_idempotent_for_existing_invoice(): void
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
            'https://api.stripe.test/v1/subscriptions/sub_sync_repeat' =>
                Http::response([
                    'id' => 'sub_sync_repeat',
                    'customer' => 'cus_sync_repeat',
                ], 200),

            'https://api.stripe.test/v1/invoices*' =>
                Http::response([
                    'data' => [
                        [
                            'id' => 'in_repeat_sync',
                            'subscription' =>
                                'sub_sync_repeat',
                            'status' => 'paid',
                            'currency' => 'brl',
                            'amount_due' => 9900,
                            'amount_paid' => 9900,
                            'amount_remaining' => 0,
                        ],
                    ],
                ], 200),
        ]);

        $subscription = $this->subscription(
            'stripe',
            'sub_sync_repeat'
        );

        $service = app(
            StripeSubscriptionInvoiceSyncService::class
        );

        $this->assertSame(
            1,
            $service->sync($subscription)
        );

        $this->assertSame(
            1,
            $service->sync($subscription)
        );

        $this->assertSame(
            1,
            SubscriptionInvoice::query()
                ->where(
                    'provider',
                    'stripe'
                )
                ->where(
                    'external_invoice_id',
                    'in_repeat_sync'
                )
                ->count()
        );
    }

    public function test_non_stripe_subscription_cannot_be_synchronized(): void
    {
        Http::fake();

        $subscription = $this->subscription(
            'mercado_pago',
            'mp_sync_123'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Subscription is not managed by Stripe.'
        );

        try {
            app(
                StripeSubscriptionInvoiceSyncService::class
            )->sync(
                $subscription
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    private function subscription(
        string $provider,
        string $externalReference
    ): Subscription {
        $tenant = Tenant::query()->create([
            'name' => 'Stripe Invoice Sync Tenant',
            'slug' => uniqid(
                'stripe-invoice-sync-',
                true
            ),
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'stripe-invoice-sync-plan-',
                false
            ),
            'name' => 'Stripe Invoice Sync Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => $provider,
            'external_reference' =>
                $externalReference,
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
    }
}