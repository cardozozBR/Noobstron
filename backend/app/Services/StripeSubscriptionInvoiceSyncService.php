<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StripeSubscriptionInvoiceSyncService
{
    public function sync(
        Subscription $subscription
    ): int {
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
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Stripe subscription lookup failed.',
                previous: $exception
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
            $invoicesResponse = Http::acceptJson()
                ->withToken($secretKey)
                ->timeout(15)
                ->get(
                    $baseUrl
                    . '/v1/invoices',
                    [
                        'customer' =>
                            $customerId,

                        'limit' =>
                            100,
                    ]
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Stripe invoice lookup failed.',
                previous: $exception
            );
        }

        if (! $invoicesResponse->successful()) {
            throw new RuntimeException(
                'Stripe invoice lookup was rejected.'
            );
        }

        $invoices = $invoicesResponse->json(
            'data',
            []
        );

        if (! is_array($invoices)) {
            return 0;
        }

        $synced = 0;

        foreach ($invoices as $invoice) {
            if (! is_array($invoice)) {
                continue;
            }

            $invoiceSubscriptionId = trim(
                (string) (
                    $invoice['subscription']
                    ?? ''
                )
            );

            if (
                $invoiceSubscriptionId !== ''
                && $invoiceSubscriptionId
                    !== $stripeSubscriptionId
            ) {
                continue;
            }

            $externalInvoiceId = trim(
                (string) (
                    $invoice['id']
                    ?? ''
                )
            );

            if ($externalInvoiceId === '') {
                continue;
            }

            $periodStart = data_get(
                $invoice,
                'lines.data.0.period.start'
            );

            $periodEnd = data_get(
                $invoice,
                'lines.data.0.period.end'
            );

            $paidAt = data_get(
                $invoice,
                'status_transitions.paid_at'
            );

            SubscriptionInvoice::query()
                ->updateOrCreate(
                    [
                        'provider' =>
                            'stripe',

                        'external_invoice_id' =>
                            $externalInvoiceId,
                    ],
                    [
                        'subscription_id' =>
                            $subscription->id,

                        'status' =>
                            $invoice['status']
                            ?? null,

                        'currency' =>
                            isset($invoice['currency'])
                                ? strtoupper(
                                    (string) $invoice['currency']
                                )
                                : null,

                        'amount_due' =>
                            (int) (
                                $invoice['amount_due']
                                ?? 0
                            ),

                        'amount_paid' =>
                            (int) (
                                $invoice['amount_paid']
                                ?? 0
                            ),

                        'amount_remaining' =>
                            (int) (
                                $invoice['amount_remaining']
                                ?? 0
                            ),

                        'billing_reason' =>
                            $invoice['billing_reason']
                            ?? null,

                        'period_start' =>
                            is_numeric($periodStart)
                                ? CarbonImmutable::createFromTimestampUTC(
                                    (int) $periodStart
                                )
                                : null,

                        'period_end' =>
                            is_numeric($periodEnd)
                                ? CarbonImmutable::createFromTimestampUTC(
                                    (int) $periodEnd
                                )
                                : null,

                        'paid_at' =>
                            is_numeric($paidAt)
                                ? CarbonImmutable::createFromTimestampUTC(
                                    (int) $paidAt
                                )
                                : null,

                        'hosted_invoice_url' =>
                            $invoice['hosted_invoice_url']
                            ?? null,

                        'invoice_pdf' =>
                            $invoice['invoice_pdf']
                            ?? null,
                    ]
                );

            $synced++;
        }

        return $synced;
    }
}