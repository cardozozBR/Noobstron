<?php

namespace App\Services;

use App\Contracts\SubscriptionPaymentProvider;
use App\Models\Subscription;
use App\Support\Currency;
use App\Support\SubscriptionCheckoutResult;
use Illuminate\Support\Facades\Http;
use Throwable;

class StripeSubscriptionProvider
    implements SubscriptionPaymentProvider
{
    public function code(): string
    {
        return 'stripe';
    }

    public function checkout(
        Subscription $subscription
    ): SubscriptionCheckoutResult {
        $secretKey = trim(
            (string) config(
                'services.stripe.secret_key'
            )
        );

        if ($secretKey === '') {
            return SubscriptionCheckoutResult::failure(
                'Stripe secret key is not configured.'
            );
        }

        $subscription->loadMissing([
            'tenant.users',
            'plan.prices',
        ]);

        $tenant = $subscription->tenant;
        $plan = $subscription->plan;

        $payer = $tenant->users
            ->firstWhere('role', 'admin')
            ?? $tenant->users->first();

        if (
            $payer === null
            || trim((string) $payer->email) === ''
        ) {
            return SubscriptionCheckoutResult::failure(
                'Subscription payer email is not available.'
            );
        }

        $currency = Currency::normalize(
            (string) $tenant->currency
        );

        $price = $plan->prices
            ->firstWhere(
                'currency',
                $currency
            );

        if ($price === null) {
            return SubscriptionCheckoutResult::failure(
                'Subscription plan price is not available.'
            );
        }

        $stripePriceId = trim(
            (string) $price->stripe_price_id
        );

        if ($stripePriceId === '') {
            return SubscriptionCheckoutResult::failure(
                'Stripe price is not configured for this plan.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.stripe.base_url',
                'https://api.stripe.com'
            ),
            '/'
        );

        $returnUrl = rtrim(
            (string) config(
                'services.stripe.return_url'
            ),
            '/'
        );

        if ($returnUrl === '') {
            return SubscriptionCheckoutResult::failure(
                'Stripe return URL is not configured.'
            );
        }

        $scheme = parse_url(
            $returnUrl,
            PHP_URL_SCHEME
        ) ?: 'https';

        $host = parse_url(
            $returnUrl,
            PHP_URL_HOST
        );

        $port = parse_url(
            $returnUrl,
            PHP_URL_PORT
        );

        if (!$host) {
            return SubscriptionCheckoutResult::failure(
                'Stripe return URL must contain a valid host.'
            );
        }

        $tenantReturnUrl =
            $scheme
            . '://'
            . $tenant->slug
            . '.'
            . $host
            . ($port ? ':' . $port : '');

        $successUrl =
            $tenantReturnUrl
            . '/billing?checkout=stripe-success';

        $cancelUrl =
            $tenantReturnUrl
            . '/billing?checkout=stripe-cancel';

        try {
            $response = Http::asForm()
                ->withToken($secretKey)
                ->timeout(15)
                ->post(
                    $baseUrl . '/v1/checkout/sessions',
                    [
                        'mode' => 'subscription',

                        'customer_email' =>
                            trim((string) $payer->email),

                        'client_reference_id' =>
                            (string) $subscription->id,

                        'metadata[subscription_id]' =>
                            (string) $subscription->id,

                        'subscription_data[metadata][subscription_id]' =>
                            (string) $subscription->id,

                        'line_items[0][price]' =>
                            $stripePriceId,

                        'line_items[0][quantity]' =>
                            1,

                        'success_url' =>
                            $successUrl,

                        'cancel_url' =>
                            $cancelUrl,
                    ]
                );
        } catch (Throwable $exception) {
            logger()->warning(
                'Stripe subscription checkout request failed.',
                [
                    'subscription_id' =>
                        $subscription->id,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            return SubscriptionCheckoutResult::failure(
                'Stripe request failed.'
            );
        }

        if (! $response->successful()) {
            logger()->warning(
                'Stripe rejected subscription checkout.',
                [
                    'subscription_id' =>
                        $subscription->id,

                    'status' =>
                        $response->status(),

                    'response' =>
                        $response->json(),
                ]
            );

            return SubscriptionCheckoutResult::failure(
                'Stripe rejected subscription checkout.'
            );
        }

        $sessionId = trim(
            (string) $response->json('id')
        );

        $checkoutUrl = trim(
            (string) $response->json('url')
        );

        if (
            $sessionId === ''
            || $checkoutUrl === ''
        ) {
            return SubscriptionCheckoutResult::failure(
                'Stripe returned an invalid checkout response.'
            );
        }

        $subscription->forceFill([
            'currency' => $currency,
            'amount_minor' => (int) $price->amount_minor,
        ])->save();

        return SubscriptionCheckoutResult::success(
            $sessionId,
            $checkoutUrl,
        );
    }
}