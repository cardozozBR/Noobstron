<?php

namespace Tests\Unit;

use App\Support\PaymentProviderEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaymentProviderEventTest extends TestCase
{
    public function test_event_preserves_provider_identifiers(): void
    {
        $event = new PaymentProviderEvent(
            'evt-123',
            'payment.approved',
            'pay-456'
        );

        $this->assertSame('evt-123', $event->eventId);
        $this->assertSame(
            'payment.approved',
            $event->normalizedType()
        );
        $this->assertSame(
            'pay-456',
            $event->externalReference
        );
    }

    public function test_event_type_is_normalized(): void
    {
        $event = new PaymentProviderEvent(
            'evt-123',
            ' Payment.Failed ',
            'pay-456',
            'Declined'
        );

        $this->assertSame(
            'payment.failed',
            $event->normalizedType()
        );

        $this->assertSame(
            'Declined',
            $event->failureReason
        );
    }

    public function test_blank_event_id_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new PaymentProviderEvent(
            ' ',
            'payment.approved',
            'pay-456'
        );
    }

    public function test_blank_external_reference_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        new PaymentProviderEvent(
            'evt-123',
            'payment.approved',
            ' '
        );
    }
}