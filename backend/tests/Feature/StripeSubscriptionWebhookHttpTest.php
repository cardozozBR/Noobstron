<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeSubscriptionWebhookHttpTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_valid_checkout_webhook_marks_subscription_as_paid(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription();

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'subscription' => 'sub_http_123',
                    'client_reference_id' =>
                        (string) $subscription->id,
                    'metadata' => [
                        'subscription_id' =>
                            (string) $subscription->id,
                    ],
                ],
            ],
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );

       $response = $this->call(
    'POST',
    '/webhooks/subscription/stripe',
    [],
    [],
    [],
    [
        'HTTP_STRIPE_SIGNATURE' =>
            $this->signature($json),
        'CONTENT_TYPE' =>
            'application/json',
    ],
    $json
);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $subscription->refresh();

        $this->assertSame(
            'stripe',
            $subscription->payment_provider
        );

        $this->assertSame(
            'sub_http_123',
            $subscription->external_reference
        );

        $this->assertNotNull(
            $subscription->paid_at
        );
    }

    public function test_deleted_webhook_cancels_matching_subscription(): void
    {
        $this->configureWebhook();

        $subscription = $this->subscription();

        $subscription->forceFill([
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_http_deleted_123',
            'paid_at' => CarbonImmutable::parse(
                '2026-08-20 10:00:00 UTC'
            ),
        ])->save();

        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_http_deleted_123',
                    'status' => 'canceled',
                    'canceled_at' => CarbonImmutable::parse(
                        '2026-08-23 12:00:00 UTC'
                    )->timestamp,
                ],
            ],
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );

        $response = $this->call(
            'POST',
            '/webhooks/subscription/stripe',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' =>
                    $this->signature($json),
                'CONTENT_TYPE' =>
                    'application/json',
            ],
            $json
        );

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $subscription->refresh();

        $this->assertSame(
            'cancelled',
            $subscription->status->value
        );

        $this->assertNull(
            $subscription->cancel_at
        );

        $this->assertSame(
            '2026-08-23 12:00:00',
            $subscription
                ->canceled_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->configureWebhook();

        $json = json_encode([
            'type' =>
                'checkout.session.completed',
            'data' => [
                'object' => [],
            ],
        ]);

        $response = $this
            ->withHeaders([
                'Stripe-Signature' =>
                    't=123,v1=invalid',
                'Content-Type' =>
                    'application/json',
            ])
            ->call(
                'POST',
                '/webhooks/subscription/stripe',
                [],
                [],
                [],
                [],
                $json
            );

        $response
            ->assertStatus(400)
            ->assertJson([
                'ok' => false,
            ]);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $this->configureWebhook();

        $response = $this->postJson(
            '/webhooks/subscription/stripe',
            [
                'type' =>
                    'checkout.session.completed',
                'data' => [
                    'object' => [],
                ],
            ]
        );

        $response
            ->assertStatus(400)
            ->assertJson([
                'ok' => false,
            ]);
    }

    private function configureWebhook(): void
    {
        config()->set(
            'services.stripe.webhook_secret',
            'TEST_STRIPE_WEBHOOK_SECRET'
        );
    }

    private function signature(
        string $payload
    ): string {
        $timestamp = '1787234400';

        $hash = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            'TEST_STRIPE_WEBHOOK_SECRET'
        );

        return 't='
            . $timestamp
            . ',v1='
            . $hash;
    }

    private function subscription(): Subscription
    {
        $tenant = Tenant::query()->create([
            'name' => 'Stripe HTTP Tenant',
            'slug' => uniqid(
                'stripe-http-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'stripe-http-plan-',
                false
            ),
            'name' => 'Stripe HTTP Plan',
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