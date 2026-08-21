<?php

namespace Tests\Feature;

use App\Contracts\PaymentWebhookNormalizer;
use App\Contracts\PaymentWebhookVerifier;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ChargeService;
use App\Services\PaymentWebhookHttpService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\PaymentProviderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;

class PaymentWebhookHttpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_is_processed(): void
    {
        $charge = $this->sentCharge(
            'webhook-valid'
        );

        $result = app(
            PaymentWebhookHttpService::class
        )->handle(
            Request::create(
                '/webhook',
                'POST'
            ),
            'test',
            $this->verifier(true),
            $this->normalizer(
                new PaymentProviderEvent(
                    'evt-valid',
                    'payment.approved',
                    'webhook-valid'
                )
            )
        );

        $this->assertSame(
            $charge->id,
            $result->id
        );

        $this->assertSame(
            'paid',
            $charge->refresh()->status->value
        );
    }

    public function test_invalid_signature_is_rejected_before_normalization(): void
    {
        $normalizerCalled = false;

        $normalizer = new class($normalizerCalled)
            implements PaymentWebhookNormalizer {
            public function __construct(
                private bool &$called
            ) {
            }

            public function normalize(
                Request $request
            ): PaymentProviderEvent {
                $this->called = true;

                return new PaymentProviderEvent(
                    'evt-invalid',
                    'payment.approved',
                    'unused'
                );
            }
        };

        try {
            app(
                PaymentWebhookHttpService::class
            )->handle(
                Request::create(
                    '/webhook',
                    'POST'
                ),
                'test',
                $this->verifier(false),
                $normalizer
            );

            $this->fail(
                'Expected RuntimeException.'
            );
        }
        catch (RuntimeException $exception) {
            $this->assertSame(
                'Invalid payment webhook signature.',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $normalizerCalled
        );
    }

    public function test_blank_provider_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            PaymentWebhookHttpService::class
        )->handle(
            Request::create(
                '/webhook',
                'POST'
            ),
            '   ',
            $this->verifier(true),
            $this->normalizer(
                new PaymentProviderEvent(
                    'evt-unused',
                    'payment.approved',
                    'unused'
                )
            )
        );
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $charge = $this->sentCharge(
            'webhook-idempotent'
        );

        $event = new PaymentProviderEvent(
            'evt-idempotent',
            'payment.approved',
            'webhook-idempotent'
        );

        $service = app(
            PaymentWebhookHttpService::class
        );

        $request = Request::create(
            '/webhook',
            'POST'
        );

        $service->handle(
            $request,
            'test',
            $this->verifier(true),
            $this->normalizer($event)
        );

        $service->handle(
            $request,
            'test',
            $this->verifier(true),
            $this->normalizer($event)
        );

        $this->assertSame(
            'paid',
            $charge->refresh()->status->value
        );

        $this->assertDatabaseCount(
            'payment_event_receipts',
            1
        );
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

    private function sentCharge(
        string $reference
    ): Charge {
        $tenant = Tenant::query()->create([
            'name' => 'Webhook HTTP Tenant',
            'slug' => uniqid(
                'webhook-http-',
                true
            ),
            'status' => 'active',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Webhook HTTP Customer',
            'type' => 'company',
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'Webhook HTTP',
            'currency' => 'BRL',
            'amount_minor' => 1000,
            'due_date' => now()->toDateString(),
        ]);

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' => $receivable->id,
        ]);

        return app(
            ChargeService::class
        )->markSent(
            $charge,
            $reference
        );
    }
}