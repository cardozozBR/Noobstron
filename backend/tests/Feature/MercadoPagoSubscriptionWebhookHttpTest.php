<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoSubscriptionWebhookHttpTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_valid_authorized_payment_webhook_marks_subscription_as_paid(): void
    {
        $this->configureProvider();

        $subscription = $this->subscription(
            'preapproval-http-123'
        );

        Http::fake([
            '*' => Http::response([
                'id' => 'authorized-payment-123',
                'preapproval_id' => 'preapproval-http-123',
                'payment' => [
                    'status' => 'approved',
                ],
            ], 200),
        ]);

        $dataId = 'authorized-payment-123';
        $requestId = 'request-123';

        $response = $this
            ->withHeaders([
                'x-request-id' => $requestId,
                'x-signature' => $this->signature(
                    $dataId,
                    $requestId
                ),
            ])
            ->postJson(
                '/webhooks/subscription/mercado-pago'
                    . '?data_id=' . $dataId
                    . '&type=subscription_authorized_payment'
            );

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertNotNull(
            $subscription
                ->refresh()
                ->paid_at
        );
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->configureProvider();

        $response = $this
            ->withHeaders([
                'x-request-id' => 'request-invalid',
                'x-signature' =>
                    'ts=123,v1=invalid',
            ])
            ->postJson(
                '/webhooks/subscription/mercado-pago'
                    . '?data_id=payment-invalid'
                    . '&type=subscription_authorized_payment'
            );

        $response
            ->assertStatus(401)
            ->assertJson([
                'ok' => false,
            ]);

        Http::assertNothingSent();
    }

    public function test_preapproval_event_does_not_mark_subscription_as_paid(): void
    {
        $this->configureProvider();

        $subscription = $this->subscription(
            'preapproval-only'
        );

        $dataId = 'preapproval-only';
        $requestId = 'request-preapproval';

        $response = $this
            ->withHeaders([
                'x-request-id' => $requestId,
                'x-signature' => $this->signature(
                    $dataId,
                    $requestId
                ),
            ])
            ->postJson(
                '/webhooks/subscription/mercado-pago'
                    . '?data_id=' . $dataId
                    . '&type=subscription_preapproval'
            );

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertNull(
            $subscription
                ->refresh()
                ->paid_at
        );

        Http::assertNothingSent();
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

        config()->set(
            'services.mercado_pago.webhook_secret',
            'TEST_WEBHOOK_SECRET'
        );
    }

    private function signature(
        string $dataId,
        string $requestId,
    ): string {
        $timestamp = '1787234400';

        $manifest =
            'id:' . $dataId . ';'
            . 'request-id:' . $requestId . ';'
            . 'ts:' . $timestamp . ';';

        $hash = hash_hmac(
            'sha256',
            $manifest,
            'TEST_WEBHOOK_SECRET'
        );

        return 'ts='
            . $timestamp
            . ',v1='
            . $hash;
    }

    private function subscription(
        string $externalReference
    ): Subscription {
        $tenant = Tenant::query()->create([
            'name' => 'Webhook HTTP Tenant',
            'slug' => uniqid(
                'webhook-http-',
                true
            ),
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'webhook-http-plan-',
                false
            ),
            'name' => 'Webhook HTTP Plan',
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