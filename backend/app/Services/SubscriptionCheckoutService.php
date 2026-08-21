<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Support\SubscriptionCheckoutResult;
use RuntimeException;

class SubscriptionCheckoutService
{
    public function __construct(
        private readonly SubscriptionPaymentProviderRegistry $providers,
    ) {
    }

    public function checkout(
        Subscription $subscription,
        string $providerCode,
    ): SubscriptionCheckoutResult {
        if (
            $subscription->status !==
            SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can start checkout.'
            );
        }

        $provider = $this->providers->resolve(
            $providerCode
        );

        $result = $provider->checkout(
            $subscription
        );

        if (
            $result->successful
            && $result->externalReference !== null
        ) {
            $subscription->forceFill([
                'payment_provider' =>
                    strtolower(trim($provider->code())),
                'external_reference' =>
                    trim($result->externalReference),
            ])->save();
        }

        return $result;
    }
}