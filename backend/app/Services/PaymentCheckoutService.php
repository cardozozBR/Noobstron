<?php

namespace App\Services;

use App\Models\Charge;
use App\Support\PaymentProviderResult;

class PaymentCheckoutService
{
    public function __construct(
        private readonly PaymentProviderRegistry $providers,
        private readonly ChargeService $charges,
    ) {
    }

    public function checkout(
        Charge $charge,
        string $providerCode,
    ): PaymentProviderResult {
        $provider = $this->providers->resolve(
            $providerCode
        );

        $result = $provider->checkout($charge);

        if (! $result->successful) {
            $this->charges->markFailed(
                $charge,
                $result->failureReason
                    ?? 'Payment provider failure.'
            );

            return $result;
        }

        $this->charges->markSent(
            $charge,
            $result->externalReference
        );

        return $result;
    }
}