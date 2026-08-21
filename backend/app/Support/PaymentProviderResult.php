<?php

namespace App\Support;

final readonly class PaymentProviderResult
{
    public function __construct(
        public bool $successful,
        public ?string $externalReference = null,
        public ?string $checkoutUrl = null,
        public ?string $failureReason = null,
    ) {
    }

    public static function success(
        ?string $externalReference = null,
        ?string $checkoutUrl = null,
    ): self {
        return new self(
            successful: true,
            externalReference: $externalReference,
            checkoutUrl: $checkoutUrl,
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