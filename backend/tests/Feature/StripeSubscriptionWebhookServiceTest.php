<?php

namespace Tests\Feature;

use App\Models\PaymentEventReceipt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\StripeSubscriptionWebhookService;
use App\Services\SubscriptionBillingService;
use App\Services\TenantWriteAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeSubscriptionWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_session_completed_marks_subscription_as_paid(): void
    {
        $this->configureWebhook();

        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_checkout_123' => Http::response([
                'id' => 'sub_checkout_123',
                'default_payment_method' => 'pm_test_123',
            ], 200),

            'https://api.stripe.test/v1/payment_methods/pm_test_123' => Http::response([
                'id' => 'pm_test_123',
                'type' => 'card',
                'card' => [
                    'brand' => 'visa',
                    'last4' => '4242',
                ],
            ], 200),
        ]);

        $subscription = $this->subscription(
            null,
            'active'
        );

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'subscription' => 'sub_checkout_123',
                    'client_reference_id' => (string) $subscription->id,
                    'metadata' => [
                        'subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ];

        $processed = $this->handle($payload);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'stripe',
            $subscription->payment_provider
        );

        $this->assertSame(
            'sub_checkout_123',
            $subscription->external_reference
        );

        $this->assertNotNull(
            $subscription->paid_at
        );

        $this->assertSame(
            'visa •••• 4242',
            $subscription->payment_method
        );
    }

    public function test_checkout_payment_is_recorded_when_payment_method_lookup_fails(): void
    {
        $this->configureWebhook();

        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_checkout_failure_123' => Http::response([], 500),
        ]);

        $subscription = $this->subscription(
            null,
            'active'
        );

        $processed = $this->handle([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'subscription' => 'sub_checkout_failure_123',
                    'client_reference_id' => (string) $subscription->id,
                    'metadata' => [
                        'subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'stripe',
            $subscription->payment_provider
        );

        $this->assertSame(
            'sub_checkout_failure_123',
            $subscription->external_reference
        );

        $this->assertNotNull(
            $subscription->paid_at
        );

        $this->assertNull(
            $subscription->payment_method
        );
    }

    public function test_invoice_payment_failed_suspends_matching_subscription(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_failed_123',
            'active'
        );

        $processed = $this->handle([
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_failed_123',
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertSame(
            'suspended',
            $subscription->refresh()->status->value
        );
    }

    public function test_invoice_paid_reactivates_and_updates_period(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_paid_123',
            'suspended'
        );

        $periodStart = CarbonImmutable::parse(
            '2026-09-20 00:00:00 UTC'
        );

        $periodEnd = CarbonImmutable::parse(
            '2026-10-20 00:00:00 UTC'
        );

        $processed = $this->handle([
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'subscription' => 'sub_paid_123',
                    'lines' => [
                        'data' => [
                            [
                                'period' => [
                                    'start' => $periodStart->timestamp,
                                    'end' => $periodEnd->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'active',
            $subscription->status->value
        );

        $this->assertSame(
            '2026-09-20 00:00:00',
            $subscription
                ->current_period_start
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-10-20 00:00:00',
            $subscription
                ->current_period_end
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertNotNull(
            $subscription->paid_at
        );
    }

    public function test_payment_failure_blocks_writes_and_paid_invoice_restores_access(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_payment_recovery_123',
            'active'
        );

        $subscription->forceFill([
            'paid_at' => CarbonImmutable::parse(
                '2026-08-20 00:01:00 UTC'
            ),
        ])->save();

        $tenant = $subscription->tenant;

        $this->assertTrue(
            app(
                TenantWriteAccessService::class
            )->allowed($tenant)
        );

        $failedProcessed = $this->handle([
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_recovery_failed_123',
                    'subscription' => 'sub_payment_recovery_123',
                    'status' => 'open',
                    'currency' => 'brl',
                    'amount_due' => 9900,
                    'amount_paid' => 0,
                    'amount_remaining' => 9900,
                    'billing_reason' => 'subscription_cycle',
                ],
            ],
        ]);

        $this->assertTrue($failedProcessed);

        $subscription->refresh();

        $this->assertSame(
            'suspended',
            $subscription->status->value
        );

        $this->assertFalse(
            app(
                TenantWriteAccessService::class
            )->allowed($tenant)
        );

        $periodStart = CarbonImmutable::parse(
            '2026-09-20 00:00:00 UTC'
        );

        $periodEnd = CarbonImmutable::parse(
            '2026-10-20 00:00:00 UTC'
        );

        $paidProcessed = $this->handle([
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_recovery_paid_123',
                    'subscription' => 'sub_payment_recovery_123',
                    'status' => 'paid',
                    'currency' => 'brl',
                    'amount_due' => 9900,
                    'amount_paid' => 9900,
                    'amount_remaining' => 0,
                    'billing_reason' => 'subscription_cycle',
                    'lines' => [
                        'data' => [
                            [
                                'period' => [
                                    'start' => $periodStart->timestamp,
                                    'end' => $periodEnd->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($paidProcessed);

        $subscription->refresh();

        $this->assertSame(
            'active',
            $subscription->status->value
        );

        $this->assertTrue(
            app(
                TenantWriteAccessService::class
            )->allowed($tenant)
        );

        $this->assertSame(
            '2026-09-20 00:00:00',
            $subscription
                ->current_period_start
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-10-20 00:00:00',
            $subscription
                ->current_period_end
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_subscription_deleted_cancels_matching_subscription(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_deleted_123',
            'active'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_deleted_123',
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertSame(
            'cancelled',
            $subscription->refresh()->status->value
        );
    }

    public function test_subscription_deleted_blocks_write_access_for_paid_tenant(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_deleted_access_123',
            'active'
        );

        $subscription->forceFill([
            'paid_at' => CarbonImmutable::parse(
                '2026-08-20 00:01:00 UTC'
            ),
        ])->save();

        $tenant = $subscription->tenant;

        $this->assertTrue(
            app(
                TenantWriteAccessService::class
            )->allowed($tenant)
        );

        $canceledAt = CarbonImmutable::parse(
            '2026-09-20 00:00:00 UTC'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_deleted_access_123',
                    'status' => 'canceled',
                    'canceled_at' => $canceledAt->timestamp,
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'cancelled',
            $subscription->status->value
        );

        $this->assertNull(
            $subscription->cancel_at
        );

        $this->assertSame(
            '2026-09-20 00:00:00',
            $subscription
                ->canceled_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertFalse(
            app(
                TenantWriteAccessService::class
            )->allowed($tenant)
        );
    }

    public function test_subscription_updated_suspends_past_due_subscription(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_updated_123',
            'active'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_updated_123',
                    'status' => 'past_due',
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertSame(
            'suspended',
            $subscription->refresh()->status->value
        );
    }

    public function test_subscription_updated_stores_scheduled_cancellation_dates(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_cancel_scheduled_123',
            'active'
        );

        $cancelAt = CarbonImmutable::parse(
            '2026-09-20 23:53:21 UTC'
        );

        $canceledAt = CarbonImmutable::parse(
            '2026-08-21 10:44:51 UTC'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_cancel_scheduled_123',
                    'status' => 'active',
                    'cancel_at' => $cancelAt->timestamp,
                    'canceled_at' => $canceledAt->timestamp,
                    'cancel_at_period_end' => false,
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'active',
            $subscription->status->value
        );

        $this->assertNotNull(
            $subscription->cancel_at
        );

        $this->assertNotNull(
            $subscription->canceled_at
        );

        $this->assertSame(
            '2026-09-20 23:53:21',
            $subscription
                ->cancel_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-21 10:44:51',
            $subscription
                ->canceled_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_subscription_updated_derives_cancel_at_from_period_end(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_cancel_period_end_123',
            'active'
        );

        $periodEnd = CarbonImmutable::parse(
            '2026-09-20 23:53:21 UTC'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_cancel_period_end_123',
                    'status' => 'active',
                    'cancel_at' => null,
                    'cancel_at_period_end' => true,
                    'current_period_end' => $periodEnd->timestamp,
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'active',
            $subscription->status->value
        );

        $this->assertSame(
            '2026-09-20 23:53:21',
            $subscription
                ->cancel_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_subscription_updated_clears_scheduled_cancellation(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_reactivated_123',
            'active'
        );

        $subscription->forceFill([
            'cancel_at' => CarbonImmutable::parse(
                '2026-09-20 23:53:21 UTC'
            ),
            'canceled_at' => CarbonImmutable::parse(
                '2026-08-21 10:44:51 UTC'
            ),
        ])->save();

        $processed = $this->handle([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_reactivated_123',
                    'status' => 'active',
                    'cancel_at' => null,
                    'canceled_at' => null,
                    'cancel_at_period_end' => false,
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'active',
            $subscription->status->value
        );

        $this->assertNull(
            $subscription->cancel_at
        );

        $this->assertNull(
            $subscription->canceled_at
        );
    }

    public function test_event_for_unknown_subscription_is_ignored(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_real_123',
            'active'
        );

        $processed = $this->handle([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_other_999',
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertSame(
            'active',
            $subscription->refresh()->status->value
        );
    }

    public function test_processing_exception_marks_receipt_as_failed(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            null,
            'active'
        );

        $billing = $this->mock(
            SubscriptionBillingService::class
        );

        $billing
            ->shouldReceive('isPaid')
            ->once()
            ->andReturn(false);

        $billing
            ->shouldReceive('markPaid')
            ->once()
            ->andThrow(
                new \RuntimeException(
                    'Simulated Stripe processing failure.'
                )
            );

        $service = app(
            StripeSubscriptionWebhookService::class
        );

        $payload = [
            'id' => 'evt_processing_failure_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'subscription' => 'sub_processing_failure_123',
                    'client_reference_id' => (string) $subscription->id,
                    'metadata' => [
                        'subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );

        try {
            $timestamp = '1787234400';

            $signature = 't='
                .$timestamp
                .',v1='
                .hash_hmac(
                    'sha256',
                    $timestamp.'.'.$json,
                    'TEST_STRIPE_WEBHOOK_SECRET'
                );

            $service->handle(
                $json,
                $signature
            );

            $this->fail(
                'Expected processing exception was not thrown.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Simulated Stripe processing failure.',
                $exception->getMessage()
            );
        }

        $receipt =
            PaymentEventReceipt::query()
                ->where('provider', 'stripe')
                ->where(
                    'event_id',
                    'evt_processing_failure_123'
                )
                ->firstOrFail();

        $this->assertSame(
            'failed',
            $receipt->status
        );

        $this->assertSame(
            1,
            $receipt->attempts
        );

        $this->assertSame(
            'Simulated Stripe processing failure.',
            $receipt->last_error
        );

        $this->assertNull(
            $receipt->processed_at
        );
    }

    public function test_failed_receipt_retry_succeeds_and_increments_attempts(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_manual_retry_123',
            'active'
        );

        $receipt = PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_manual_retry_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_manual_retry_123',
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'Previous failure.',
            'payload' => [
                'type' => 'customer.subscription.updated',
                'data' => [
                    'object' => [
                        'id' => 'sub_manual_retry_123',
                        'status' => 'active',
                        'cancel_at' => null,
                        'canceled_at' => null,
                        'cancel_at_period_end' => false,
                    ],
                ],
            ],
            'processed_at' => null,
        ]);

        $processed = app(
            StripeSubscriptionWebhookService::class
        )->retry($receipt);

        $this->assertTrue($processed);

        $receipt->refresh();

        $this->assertSame(
            'processed',
            $receipt->status
        );

        $this->assertSame(
            2,
            $receipt->attempts
        );

        $this->assertNull(
            $receipt->last_error
        );

        $this->assertNotNull(
            $receipt->processed_at
        );

        $this->assertSame(
            'active',
            $subscription->refresh()->status->value
        );
    }

    public function test_failed_receipt_retry_failure_preserves_new_attempt_and_error(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            null,
            'active'
        );

        $billing = $this->mock(
            SubscriptionBillingService::class
        );

        $billing
            ->shouldReceive('isPaid')
            ->once()
            ->andReturn(false);

        $billing
            ->shouldReceive('markPaid')
            ->once()
            ->andThrow(
                new \RuntimeException(
                    'Retry processing failed.'
                )
            );

        $receipt = PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_manual_retry_failure_123',
            'event_type' => 'checkout.session.completed',
            'external_reference' => 'sub_manual_retry_failure_123',
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'Previous failure.',
            'payload' => [
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'payment_status' => 'paid',
                        'subscription' => 'sub_manual_retry_failure_123',
                        'client_reference_id' => (string) $subscription->id,
                        'metadata' => [
                            'subscription_id' => (string) $subscription->id,
                        ],
                    ],
                ],
            ],
            'processed_at' => null,
        ]);

        $service = app(
            StripeSubscriptionWebhookService::class
        );

        try {
            $service->retry($receipt);

            $this->fail(
                'Expected retry exception was not thrown.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Retry processing failed.',
                $exception->getMessage()
            );
        }

        $receipt->refresh();

        $this->assertSame(
            'failed',
            $receipt->status
        );

        $this->assertSame(
            2,
            $receipt->attempts
        );

        $this->assertSame(
            'Retry processing failed.',
            $receipt->last_error
        );

        $this->assertNull(
            $receipt->processed_at
        );
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->configureWebhook();

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [],
            ],
        ]);

        $processed = app(
            StripeSubscriptionWebhookService::class
        )->handle(
            $payload,
            't=123,v1=invalid'
        );

        $this->assertFalse(
            $processed
        );
    }

    private function configureWebhook(): void
    {
        config()->set(
            'services.stripe.webhook_secret',
            'TEST_STRIPE_WEBHOOK_SECRET'
        );
    }

    private function handle(
        array $event
    ): bool {
        $payload = json_encode(
            $event,
            JSON_UNESCAPED_SLASHES
        );

        $timestamp = '1787234400';

        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            'TEST_STRIPE_WEBHOOK_SECRET'
        );

        return app(
            StripeSubscriptionWebhookService::class
        )->handle(
            $payload,
            't='.$timestamp
                .',v1='.$signature
        );
    }

    private function subscription(
        ?string $externalReference,
        string $status,
    ): Subscription {
        $tenant = Tenant::query()->create([
            'name' => 'Stripe Webhook Tenant',
            'slug' => uniqid(
                'stripe-webhook-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'stripe-webhook-plan-',
                false
            ),
            'name' => 'Stripe Webhook Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'payment_provider' => $externalReference !== null
                    ? 'stripe'
                    : null,
            'external_reference' => $externalReference,
            'current_period_start' => CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
            'current_period_end' => CarbonImmutable::parse(
                '2026-09-20 00:00:00 UTC'
            ),
        ]);
    }

    public function test_invoice_paid_persists_subscription_invoice(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_invoice_paid_123',
            'active'
        );

        $periodStart =
            CarbonImmutable::parse(
                '2026-09-20 00:00:00 UTC'
            );

        $periodEnd =
            CarbonImmutable::parse(
                '2026-10-20 00:00:00 UTC'
            );

        $paidAt =
            CarbonImmutable::parse(
                '2026-09-20 00:01:00 UTC'
            );

        $processed = $this->handle([
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_paid_123',
                    'subscription' => 'sub_invoice_paid_123',
                    'status' => 'paid',
                    'currency' => 'brl',
                    'amount_due' => 24900,
                    'amount_paid' => 24900,
                    'amount_remaining' => 0,
                    'billing_reason' => 'subscription_cycle',
                    'hosted_invoice_url' => 'https://invoice.test/paid',
                    'invoice_pdf' => 'https://invoice.test/paid.pdf',
                    'status_transitions' => [
                        'paid_at' => $paidAt->timestamp,
                    ],
                    'lines' => [
                        'data' => [
                            [
                                'period' => [
                                    'start' => $periodStart->timestamp,
                                    'end' => $periodEnd->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'subscription_id' => $subscription->id,
                'provider' => 'stripe',
                'external_invoice_id' => 'in_paid_123',
                'status' => 'paid',
                'currency' => 'BRL',
                'amount_due' => 24900,
                'amount_paid' => 24900,
                'amount_remaining' => 0,
                'billing_reason' => 'subscription_cycle',
            ]
        );
    }

    public function test_invoice_payment_failed_persists_subscription_invoice(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_invoice_failed_123',
            'active'
        );

        $processed = $this->handle([
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_failed_123',
                    'subscription' => 'sub_invoice_failed_123',
                    'status' => 'open',
                    'currency' => 'brl',
                    'amount_due' => 24900,
                    'amount_paid' => 0,
                    'amount_remaining' => 24900,
                    'billing_reason' => 'subscription_cycle',
                    'lines' => [
                        'data' => [
                            [
                                'period' => [
                                    'start' => CarbonImmutable::parse(
                                        '2026-09-20 00:00:00 UTC'
                                    )->timestamp,
                                    'end' => CarbonImmutable::parse(
                                        '2026-10-20 00:00:00 UTC'
                                    )->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($processed);

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'subscription_id' => $subscription->id,
                'provider' => 'stripe',
                'external_invoice_id' => 'in_failed_123',
                'status' => 'open',
                'currency' => 'BRL',
                'amount_due' => 24900,
                'amount_paid' => 0,
                'amount_remaining' => 24900,
                'billing_reason' => 'subscription_cycle',
            ]
        );

        $this->assertSame(
            'suspended',
            $subscription->refresh()->status->value
        );
    }

    public function test_repeated_invoice_event_does_not_duplicate_subscription_invoice(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription(
            'sub_invoice_repeat_123',
            'active'
        );

        $event = [
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_repeat_123',
                    'subscription' => 'sub_invoice_repeat_123',
                    'status' => 'paid',
                    'currency' => 'brl',
                    'amount_due' => 9900,
                    'amount_paid' => 9900,
                    'amount_remaining' => 0,
                    'billing_reason' => 'subscription_cycle',
                    'status_transitions' => [
                        'paid_at' => CarbonImmutable::parse(
                            '2026-09-20 00:01:00 UTC'
                        )->timestamp,
                    ],
                    'lines' => [
                        'data' => [
                            [
                                'period' => [
                                    'start' => CarbonImmutable::parse(
                                        '2026-09-20 00:00:00 UTC'
                                    )->timestamp,
                                    'end' => CarbonImmutable::parse(
                                        '2026-10-20 00:00:00 UTC'
                                    )->timestamp,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertTrue(
            $this->handle($event)
        );

        $this->assertTrue(
            $this->handle($event)
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
                    'in_repeat_123'
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'subscription_id' => $subscription->id,
                'external_invoice_id' => 'in_repeat_123',
                'amount_paid' => 9900,
            ]
        );
    }
}
