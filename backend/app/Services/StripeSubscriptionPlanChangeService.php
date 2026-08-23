<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Currency;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StripeSubscriptionPlanChangeService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {
    }

    public function change(
        Subscription $subscription,
        Plan $targetPlan,
    ): Subscription {
        if (
            $subscription->status
            !== SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Only active subscriptions can change plan.'
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

        if (
            $subscription->plan_id
            === $targetPlan->id
        ) {
            return $subscription->refresh();
        }

        $subscription->loadMissing([
            'tenant',
        ]);

        $targetPlan->loadMissing([
            'prices',
        ]);

        $currency = Currency::normalize(
            (string) $subscription
                ->tenant
                ->currency
        );

        $targetPrice = $targetPlan
            ->prices
            ->firstWhere(
                'currency',
                $currency
            );

        if ($targetPrice === null) {
            throw new RuntimeException(
                'Target plan price is not available.'
            );
        }

        $stripePriceId = trim(
            (string) $targetPrice->stripe_price_id
        );

        if ($stripePriceId === '') {
            throw new RuntimeException(
                'Stripe price is not configured for target plan.'
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
            $lookupResponse = Http::acceptJson()
                ->withToken($secretKey)
                ->timeout(15)
                ->get(
                    $baseUrl
                    . '/v1/subscriptions/'
                    . rawurlencode(
                        $stripeSubscriptionId
                    )
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Stripe subscription lookup failed.',
                previous: $exception
            );
        }

        if (! $lookupResponse->successful()) {
            throw new RuntimeException(
                'Stripe subscription lookup was rejected.'
            );
        }

        $items = $lookupResponse->json(
            'items.data',
            []
        );

        if (
            ! is_array($items)
            || count($items) !== 1
        ) {
            throw new RuntimeException(
                'Stripe subscription must contain exactly one item.'
            );
        }

        $subscriptionItemId = trim(
            (string) (
                $items[0]['id']
                ?? ''
            )
        );

        if ($subscriptionItemId === '') {
            throw new RuntimeException(
                'Stripe subscription item is missing.'
            );
        }

        try {
            $updateResponse = Http::asForm()
                ->withToken($secretKey)
                ->timeout(15)
                ->post(
                    $baseUrl
                    . '/v1/subscriptions/'
                    . rawurlencode(
                        $stripeSubscriptionId
                    ),
                    [
                        'items[0][id]' =>
                            $subscriptionItemId,

                        'items[0][price]' =>
                            $stripePriceId,

                        'proration_behavior' =>
                            'create_prorations',
                    ]
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Stripe plan change request failed.',
                previous: $exception
            );
        }

        if (! $updateResponse->successful()) {
            logger()->warning(
                'Stripe rejected subscription plan change.',
                [
                    'subscription_id' =>
                        $subscription->id,

                    'stripe_subscription_id' =>
                        $stripeSubscriptionId,

                    'stripe_subscription_item_id' =>
                        $subscriptionItemId,

                    'target_plan_id' =>
                        $targetPlan->id,

                    'stripe_price_id' =>
                        $stripePriceId,

                    'status' =>
                        $updateResponse->status(),

                    'response' =>
                        $updateResponse->json(),
                ]
            );

            throw new RuntimeException(
                'Stripe rejected subscription plan change.'
            );
        }

        $updatedSubscription = $this->subscriptions
            ->changePlan(
                $subscription,
                $targetPlan
            );

        $updatedSubscription->forceFill([
            'currency' => $currency,
            'amount_minor' =>
                (int) $targetPrice->amount_minor,
        ])->save();

        return $updatedSubscription->refresh();
    }
}