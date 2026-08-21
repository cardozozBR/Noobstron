<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StripeCustomerPortalService
{
    public function createSession(
        Subscription $subscription
    ): string {
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

        $returnUrl = trim(
            (string) config(
                'services.stripe.portal_return_url'
            )
        );

        if ($returnUrl === '') {
            throw new RuntimeException(
                'Stripe portal return URL is not configured.'
            );
        }

        try {
            $subscriptionResponse = Http::acceptJson()
                ->withToken($secretKey)
                ->timeout(15)
                ->get(
                    $baseUrl
                    . '/v1/subscriptions/'
                    . rawurlencode(
                        $stripeSubscriptionId
                    )
                );
        } catch (Throwable) {
            throw new RuntimeException(
                'Stripe subscription lookup failed.'
            );
        }

        if (! $subscriptionResponse->successful()) {
            throw new RuntimeException(
                'Stripe subscription lookup was rejected.'
            );
        }

        $customerId = trim(
            (string) $subscriptionResponse->json(
                'customer'
            )
        );

        if ($customerId === '') {
            throw new RuntimeException(
                'Stripe customer reference is missing.'
            );
        }

        try {
            $portalResponse = Http::asForm()
                ->withToken($secretKey)
                ->timeout(15)
                ->post(
                    $baseUrl
                    . '/v1/billing_portal/sessions',
                    [
                        'customer' =>
                            $customerId,
                        'return_url' =>
                            $returnUrl,
                    ]
                );
        } catch (Throwable) {
            throw new RuntimeException(
                'Stripe portal request failed.'
            );
        }

        if (! $portalResponse->successful()) {
            throw new RuntimeException(
                'Stripe rejected customer portal request.'
            );
        }

        $portalUrl = trim(
            (string) $portalResponse->json('url')
        );

        if ($portalUrl === '') {
            throw new RuntimeException(
                'Stripe returned an invalid portal response.'
            );
        }

        return $portalUrl;
    }
}