<?php

namespace App\Services;

use App\Contracts\SubscriptionPaymentProvider;
use App\Models\Subscription;
use App\Support\Currency;
use App\Support\SubscriptionCheckoutResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MercadoPagoSubscriptionProvider
    implements SubscriptionPaymentProvider
{
    public function code(): string
    {
        return 'mercado_pago';
    }

    public function checkout(
        Subscription $subscription
    ): SubscriptionCheckoutResult {
        $accessToken = trim(
            (string) config(
                'services.mercado_pago.access_token'
            )
        );

        if ($accessToken === '') {
            return SubscriptionCheckoutResult::failure(
                'Mercado Pago access token is not configured.'
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

        $payerEmail = trim(
    (string) config(
        'services.mercado_pago.test_payer_email'
    )
);

if ($payerEmail === '') {
    $payerEmail = trim((string) $payer->email);
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

        $amount = $price->amount_minor
            / Currency::factor($currency);

        $baseUrl = rtrim(
            (string) config(
                'services.mercado_pago.base_url',
                'https://api.mercadopago.com'
            ),
            '/'
        );

        $externalReference =
            'subscription-' . $subscription->id;

      $backUrl = rtrim(
    (string) config('services.mercado_pago.back_url'),
    '/'
);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($accessToken)
                ->timeout(15)
                ->post(
                    $baseUrl . '/preapproval',
                    [
                        'reason' =>
                            $plan->name,
                        'external_reference' =>
                            $externalReference,
                     'payer_email' =>
                      $payerEmail,
                        'auto_recurring' => [
                            'frequency' => 1,
                            'frequency_type' =>
                                'months',
                            'transaction_amount' =>
                                $amount,
                            'currency_id' =>
                                $currency,
                        ],
                        'back_url' =>
                            $backUrl,
                            'status' => 'pending',
                    ]
                );
        } catch (Throwable $exception) {
            return SubscriptionCheckoutResult::failure(
                'Mercado Pago request failed.'
            );
        }

        if (! $response->successful()) {
            logger()->warning(
                'Mercado Pago rejected subscription checkout.',
                [
                    'subscription_id' => $subscription->id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]
            );

            return SubscriptionCheckoutResult::failure(
                'Mercado Pago rejected subscription checkout.'
            );
        }

        $providerId = trim(
            (string) $response->json('id')
        );

        $checkoutUrl = trim(
            (string) $response->json(
                'init_point'
            )
        );

        if (
            $providerId === ''
            || $checkoutUrl === ''
        ) {
            return SubscriptionCheckoutResult::failure(
                'Mercado Pago returned an invalid checkout response.'
            );
        }

        return SubscriptionCheckoutResult::success(
            $providerId,
            $checkoutUrl,
        );
    }
}