<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use RuntimeException;

class SubscriptionBillingService
{
    public function markPaid(
        Subscription $subscription,
        string $provider,
        string $externalReference,
        ?string $paymentMethod = null,
        ?CarbonImmutable $paidAt = null,
    ): Subscription {
        if (
            $subscription->status !==
            SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can be paid.'
            );
        }

        $provider = strtolower(trim($provider));
        $externalReference = trim($externalReference);

        if ($provider === '') {
            throw new RuntimeException(
                'Payment provider is required.'
            );
        }

        if ($externalReference === '') {
            throw new RuntimeException(
                'Payment external reference is required.'
            );
        }

        if ($paymentMethod !== null) {
            $paymentMethod = strtolower(
                trim($paymentMethod)
            );

            if ($paymentMethod === '') {
                $paymentMethod = null;
            }
        }

        $subscription->forceFill([
            'payment_provider' => $provider,
            'external_reference' => $externalReference,
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAt
                ?? CarbonImmutable::now('UTC'),
        ])->save();

        return $subscription->refresh();
    }

    public function isPaid(
        Subscription $subscription
    ): bool {
        return $subscription->paid_at !== null
            && $subscription->payment_provider !== null
            && $subscription->external_reference !== null;
    }
}