<?php

namespace Tests\Unit;

use App\Contracts\PaymentWebhookNormalizer;
use App\Contracts\PaymentWebhookVerifier;
use App\Support\PaymentProviderEvent;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentWebhookContractTest extends TestCase
{
    public function test_verifier_contract_accepts_request(): void
    {
        $verifier = new class implements PaymentWebhookVerifier {
            public function verify(
                Request $request
            ): bool {
                return $request->header(
                    'X-Test-Signature'
                ) === 'valid';
            }
        };

        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_TEST_SIGNATURE' => 'valid',
            ]
        );

        $this->assertTrue(
            $verifier->verify($request)
        );
    }

    public function test_normalizer_contract_returns_payment_event(): void
    {
        $normalizer = new class implements PaymentWebhookNormalizer {
            public function normalize(
                Request $request
            ): PaymentProviderEvent {
                return new PaymentProviderEvent(
                    eventId: 'evt-123',
                    type: 'payment.approved',
                    externalReference: 'charge-123',
                );
            }
        };

        $event = $normalizer->normalize(
            Request::create(
                '/webhook',
                'POST'
            )
        );

        $this->assertSame(
            'evt-123',
            $event->eventId
        );

        $this->assertSame(
            'payment.approved',
            $event->normalizedType()
        );

        $this->assertSame(
            'charge-123',
            $event->externalReference
        );
    }
}