<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\MercadoPagoSubscriptionWebhookService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoSubscriptionWebhookServiceTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_approved_authorized_payment_marks_subscription_as_paid(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.mercadopago.test/authorized_payments/payment-123' =>
                Http::response([
                    'id' => 'payment-123',
                    'preapproval_id' => 'preapproval-123',
                    'payment' => [
                        'status' => 'approved',
                    ],
                ], 200),
        ]);

        $subscription = $this->subscription(
            'preapproval-123'
        );

        $processed = app(
            MercadoPagoSubscriptionWebhookService::class
        )->processAuthorizedPayment(
            'payment-123'
        );

        $this->assertTrue($processed);

        $subscription->refresh();

        $this->assertSame(
            'mercado_pago',
            $subscription->payment_provider
        );

        $this->assertSame(
            'preapproval-123',
            $subscription->external_reference
        );

        $this->assertNotNull(
            $subscription->paid_at
        );
    }

    public function test_pending_authorized_payment_does_not_mark_subscription_as_paid(): void
    {
        $this->configureProvider();

        Http::fake([
            '*' => Http::response([
                'id' => 'payment-456',
                'preapproval_id' => 'preapproval-456',
                'payment' => [
                    'status' => 'pending',
                ],
            ], 200),
        ]);

        $subscription = $this->subscription(
            'preapproval-456'
        );

        $processed = app(
            MercadoPagoSubscriptionWebhookService::class
        )->processAuthorizedPayment(
            'payment-456'
        );

        $this->assertFalse($processed);

        $this->assertNull(
            $subscription->refresh()->paid_at
        );
    }

    public function test_unknown_preapproval_is_ignored(): void
    {
        $this->configureProvider();

        Http::fake([
            '*' => Http::response([
                'id' => 'payment-missing',
                'preapproval_id' => 'missing-preapproval',
                'payment' => [
                    'status' => 'approved',
                ],
            ], 200),
        ]);

        $processed = app(
            MercadoPagoSubscriptionWebhookService::class
        )->processAuthorizedPayment(
            'payment-missing'
        );

        $this->assertFalse($processed);
    }

    public function test_already_paid_subscription_is_idempotent(): void
    {
        $this->configureProvider();

        Http::fake([
            '*' => Http::response([
                'id' => 'payment-repeat',
                'preapproval_id' => 'preapproval-repeat',
                'payment' => [
                    'status' => 'approved',
                ],
            ], 200),
        ]);

        $subscription = $this->subscription(
            'preapproval-repeat'
        );

        $subscription->forceFill([
            'paid_at' => CarbonImmutable::parse(
                '2026-08-20 10:00:00 UTC'
            ),
        ])->save();

        $processed = app(
            MercadoPagoSubscriptionWebhookService::class
        )->processAuthorizedPayment(
            'payment-repeat'
        );

        $this->assertTrue($processed);

        $this->assertSame(
            '2026-08-20 10:00:00',
            $subscription
                ->refresh()
                ->paid_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );
    }

    private function configureProvider(): void
    {
        config()->set(
            'services.mercado_pago.access_token',
            'TEST_TOKEN'
        );

        config()->set(
            'services.mercado_pago.base_url',
            'https://api.mercadopago.test'
        );
    }

    private function subscription(
        string $externalReference
    ): Subscription {
        $tenant = Tenant::query()->create([
            'name' => 'Authorized Payment Tenant',
            'slug' => uniqid(
                'authorized-payment-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'authorized-payment-plan-',
                false
            ),
            'name' => 'Authorized Payment Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => 'mercado_pago',
            'external_reference' => $externalReference,
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