<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StripeSubscriptionCancellationService
{
    public function cancelAtPeriodEnd(
        Subscription $subscription
    ): Subscription {
        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can be cancelled.'
            );
        }

        if (
            $subscription->payment_provider
            !== 'stripe'
        ) {
            throw new RuntimeException(
                'Subscription is not managed by Stripe.'
            );
        }

        $stripeSubscriptionId = trim(
            (string) $subscription->external_reference
        );

        if ($stripeSubscriptionId === '') {
            throw new RuntimeException(
                'Stripe subscription reference is missing.'
            );
        }

        $secretKey = trim(
            (string) config(
                'services.stripe.secret_key'
            )
        );

        if ($secretKey === '') {
            throw new RuntimeException(
                'Stripe secret key is not configured.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.stripe.base_url',
                'https://api.stripe.com'
            ),
            '/'
        );

        try {
            $response = Http::asForm()
                ->withToken($secretKey)
                ->timeout(15)
                ->post(
                    $baseUrl
                    . '/v1/subscriptions/'
                    . rawurlencode(
                        $stripeSubscriptionId
                    ),
                    [
                        'cancel_at_period_end' => 'true',
                    ]
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Stripe subscription cancellation request failed.',
                previous: $exception
            );
        }

        if (! $response->successful()) {
            logger()->warning(
                'Stripe rejected subscription cancellation.',
                [
                    'subscription_id' =>
                        $subscription->id,

                    'stripe_subscription_id' =>
                        $stripeSubscriptionId,

                    'status' =>
                        $response->status(),

                    'response' =>
                        $response->json(),
                ]
            );

            throw new RuntimeException(
                'Stripe rejected subscription cancellation.'
            );
        }

        $cancelAt = $response->json('cancel_at');

        if (! is_numeric($cancelAt)) {
            $cancelAt = $response->json(
                'current_period_end'
            );
        }

        $subscription->forceFill([
            'cancel_at' =>
                is_numeric($cancelAt)
                    ? CarbonImmutable::createFromTimestampUTC(
                        (int) $cancelAt
                    )
                    : $subscription->current_period_end,
        ])->save();

        return $subscription->refresh();
    }
}
