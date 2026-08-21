<?php

namespace Tests\Feature;

use App\Contracts\PaymentWebhookNormalizer;
use App\Contracts\PaymentWebhookVerifier;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_processes_active_tenant(): void
    {
        [$tenant, $charge] = $this->sentCharge();

        app()->instance(
            'payment.webhook.verifier.test',
            $this->verifier(true)
        );

        app()->instance(
            'payment.webhook.normalizer.test',
            $this->normalizer(
                new PaymentProviderEvent(
                    'evt-controller',
                    'payment.approved',
                    'controller-ref'
                )
            )
        );

        $response = $this->postJson(
            '/webhooks/payment/'
                . $tenant->slug
                . '/test'
        );

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'charge_id' => $charge->id,
            ]);

        $this->assertSame(
            'paid',
            $charge->refresh()->status->value
        );
    }

    public function test_invalid_signature_returns_401(): void
    {
        [$tenant] = $this->sentCharge();

        app()->instance(
            'payment.webhook.verifier.test',
            $this->verifier(false)
        );

        app()->instance(
            'payment.webhook.normalizer.test',
            $this->normalizer(
                new PaymentProviderEvent(
                    'evt-invalid',
                    'payment.approved',
                    'controller-ref'
                )
            )
        );

        $this->postJson(
            '/webhooks/payment/'
                . $tenant->slug
                . '/test'
        )->assertStatus(401);
    }

    public function test_unknown_provider_returns_404(): void
    {
        [$tenant] = $this->sentCharge();

        $this->postJson(
            '/webhooks/payment/'
                . $tenant->slug
                . '/missing'
        )->assertStatus(404);
    }

    public function test_inactive_tenant_returns_404(): void
    {
        [$tenant] = $this->sentCharge();

        $tenant->update([
            'status' => 'blocked',
        ]);

        app()->instance(
            'payment.webhook.verifier.test',
            $this->verifier(true)
        );

        app()->instance(
            'payment.webhook.normalizer.test',
            $this->normalizer(
                new PaymentProviderEvent(
                    'evt-blocked',
                    'payment.approved',
                    'controller-ref'
                )
            )
        );

        $this->postJson(
            '/webhooks/payment/'
                . $tenant->slug
                . '/test'
        )->assertStatus(404);
    }

    private function sentCharge(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Webhook Controller Tenant',
            'slug' => uniqid('webhook-controller-', true),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Webhook Controller Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Webhook Controller',
            'currency' => 'BRL',
            'amount_minor' => 1000,
            'due_date' => now()->toDateString(),
        ]);

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' => $receivable->id,
        ]);

        $charge = app(
            ChargeService::class
        )->markSent(
            $charge,
            'controller-ref'
        );

        return [$tenant, $charge];
    }

    private function verifier(
        bool $result
    ): PaymentWebhookVerifier {
        return new class($result)
            implements PaymentWebhookVerifier {
            public function __construct(
                private bool $result
            ) {
            }

            public function verify(
                Request $request
            ): bool {
                return $this->result;
            }
        };
    }

    private function normalizer(
        PaymentProviderEvent $event
    ): PaymentWebhookNormalizer {
        return new class($event)
            implements PaymentWebhookNormalizer {
            public function __construct(
                private PaymentProviderEvent $event
            ) {
            }

            public function normalize(
                Request $request
            ): PaymentProviderEvent {
                return $this->event;
            }
        };
    }
}