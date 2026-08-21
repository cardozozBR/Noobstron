<?php

namespace App\Support;

final readonly class SubscriptionCheckoutResult
{
    public function __construct(
        public bool $successful,
        public ?string $externalReference = null,
        public ?string $checkoutUrl = null,
        public ?string $failureReason = null,
    ) {
    }

    public static function success(
        string $externalReference,
        string $checkoutUrl,
    ): self {
        return new self(
            successful: true,
            externalReference: trim(
                $externalReference
            ),
            checkoutUrl: trim(
                $checkoutUrl
            ),
        );
    }

    public static function failure(
        string $reason
    ): self {
        return new self(
            successful: false,
            failureReason: trim($reason),
        );
    }
}