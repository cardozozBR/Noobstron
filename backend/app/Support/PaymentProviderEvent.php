<?php

namespace App\Support;

use RuntimeException;

final readonly class PaymentProviderEvent
{
    public function __construct(
        public string $eventId,
        public string $type,
        public string $externalReference,
        public ?string $failureReason = null,
    ) {
        if (trim($this->eventId) === '') {
            throw new RuntimeException(
                'Payment event id is required.'
            );
        }

        if (trim($this->type) === '') {
            throw new RuntimeException(
                'Payment event type is required.'
            );
        }

        if (trim($this->externalReference) === '') {
            throw new RuntimeException(
                'Payment external reference is required.'
            );
        }
    }

    public function normalizedType(): string
    {
        return strtolower(trim($this->type));
    }
}