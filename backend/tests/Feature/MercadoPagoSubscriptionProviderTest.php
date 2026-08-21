<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoSubscriptionProvider;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoSubscriptionProviderTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_mercado_pago_subscription(): void
    {
        config()->set(
            'services.mercado_pago.access_token',
            'TEST_TOKEN'
        );

        config()->set(
            'services.mercado_pago.base_url',
            'https://api.mercadopago.test'
        );

        Http::fake([
            'https://api.mercadopago.test/preapproval' =>
                Http::response([
                    'id' => 'mp-preapproval-123',
                    'init_point' =>
                        'https://mercadopago.test/checkout/123',
                ], 201),
        ]);

        $result = app(
            MercadoPagoSubscriptionProvider::class
        )->checkout(
            $this->subscription()
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            'mp-preapproval-123',
            $result->externalReference
        );

        $this->assertSame(
            'https://mercadopago.test/checkout/123',
            $result->checkoutUrl
        );

        Http::assertSent(
            function ($request): bool {
                $payload = $request->data();

                return $request->method() === 'POST'
                    && $request->url() ===
                        'https://api.mercadopago.test/preapproval'
                    && $payload['payer_email']
                        === 'admin@example.com'
                    && $payload['auto_recurring']['frequency']
                        === 1
                    && $payload['auto_recurring']['frequency_type']
                        === 'months'
                    && $payload['auto_recurring']['transaction_amount']
                        === 49.9
                    && $payload['auto_recurring']['currency_id']
                        === 'BRL';
            }
        );
    }

    public function test_missing_access_token_returns_failure(): void
    {
        config()->set(
            'services.mercado_pago.access_token',
            ''
        );

        $result = app(
            MercadoPagoSubscriptionProvider::class
        )->checkout(
            $this->subscription()
        );

        $this->assertFalse(
            $result->successful
        );

        Http::assertNothingSent();
    }

    public function test_provider_error_returns_failure(): void
    {
        config()->set(
            'services.mercado_pago.access_token',
            'TEST_TOKEN'
        );

        config()->set(
            'services.mercado_pago.base_url',
            'https://api.mercadopago.test'
        );

        Http::fake([
            '*' => Http::response(
                ['message' => 'error'],
                400
            ),
        ]);

        $result = app(
            MercadoPagoSubscriptionProvider::class
        )->checkout(
            $this->subscription()
        );

        $this->assertFalse(
            $result->successful
        );
    }

    private function subscription(): Subscription
    {
        $tenant = Tenant::query()->create([
            'name' => 'Mercado Pago Tenant',
            'slug' => uniqid(
                'mercado-pago-',
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
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $plan = Plan::query()->create([
            'code' => uniqid(
                'mercado-pago-plan-',
                false
            ),
            'name' => 'Plano Pro',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 4990,
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