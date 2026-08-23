<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Throwable;

class StripeSubscriptionWebhookService
{
    public function __construct(
        private readonly SubscriptionBillingService $billing,
    ) {
    }

    public function handle(
        string $payload,
        string $signature,
    ): bool {
        $secret = trim(
            (string) config(
                'services.stripe.webhook_secret'
            )
        );

        if (
            $secret === ''
            || trim($signature) === ''
        ) {
            return false;
        }

        if (! $this->verifySignature(
            $payload,
            $signature,
            $secret,
        )) {
            return false;
        }

        $event = json_decode(
            $payload,
            true
        );

        if (! is_array($event)) {
            return false;
        }

        $type = trim(
            (string) ($event['type'] ?? '')
        );

        $object = $event['data']['object']
            ?? null;

        if (! is_array($object)) {
            return true;
        }

        if (
            $type ===
            'checkout.session.completed'
        ) {
            $this->processCheckoutSession(
                $object
            );
        }

        if (
    $type ===
    'invoice.paid'
) {
    $this->processInvoicePaid(
        $object
    );
}

if (
    $type ===
    'invoice.payment_failed'
) {
    $this->processInvoicePaymentFailed(
        $object
    );
}

if (
    $type ===
    'customer.subscription.deleted'
) {
    $this->processSubscriptionDeleted(
        $object
    );
}

if (
    $type ===
    'customer.subscription.updated'
) {
    $this->processSubscriptionUpdated(
        $object
    );
}

return true;

    }

    private function processCheckoutSession(
        array $session
    ): void {
        $subscriptionId = data_get(
            $session,
            'metadata.subscription_id'
        );

        if ($subscriptionId === null) {
            $subscriptionId =
                $session['client_reference_id']
                ?? null;
        }

        if (! is_numeric($subscriptionId)) {
            return;
        }

        $paymentStatus = strtolower(
            trim(
                (string) (
                    $session['payment_status']
                    ?? ''
                )
            )
        );

        if ($paymentStatus !== 'paid') {
            return;
        }

        $stripeSubscriptionId = trim(
            (string) (
                $session['subscription']
                ?? ''
            )
        );

        if ($stripeSubscriptionId === '') {
            return;
        }

        $subscription =
            Subscription::withoutGlobalScopes()
                ->find((int) $subscriptionId);

        if ($subscription === null) {
            return;
        }

        if ($this->billing->isPaid(
            $subscription
        )) {
            return;
        }

$paymentMethod = null;

$secretKey = trim(
    (string) config(
        'services.stripe.secret_key'
    )
);

$baseUrl = rtrim(
    (string) config(
        'services.stripe.base_url',
        'https://api.stripe.com'
    ),
    '/'
);

if ($secretKey !== '') {
    try {
        $stripeSubscriptionResponse = Http::acceptJson()
            ->withToken($secretKey)
            ->timeout(15)
            ->get(
                $baseUrl
                . '/v1/subscriptions/'
                . rawurlencode(
                    $stripeSubscriptionId
                )
            );

        if ($stripeSubscriptionResponse->successful()) {
            $paymentMethodId = trim(
                (string) $stripeSubscriptionResponse->json(
                    'default_payment_method'
                )
            );

            if ($paymentMethodId !== '') {
                $paymentMethodResponse = Http::acceptJson()
                    ->withToken($secretKey)
                    ->timeout(15)
                    ->get(
                        $baseUrl
                        . '/v1/payment_methods/'
                        . rawurlencode(
                            $paymentMethodId
                        )
                    );

                if ($paymentMethodResponse->successful()) {
                    $brand = trim(
                        (string) $paymentMethodResponse->json(
                            'card.brand'
                        )
                    );

                    $last4 = trim(
                        (string) $paymentMethodResponse->json(
                            'card.last4'
                        )
                    );

                    if (
                        $brand !== ''
                        && $last4 !== ''
                    ) {
                        $paymentMethod =
                            $brand
                            . ' •••• '
                            . $last4;
                    }
                }
            }
        }
    } catch (Throwable) {
        $paymentMethod = null;
    }
}

        $this->billing->markPaid(
    $subscription,
    'stripe',
    $stripeSubscriptionId,
    $paymentMethod,
    CarbonImmutable::now('UTC'),
);
    }


    private function syncInvoice(
        Subscription $subscription,
        array $invoice
    ): void {
        $externalInvoiceId = trim(
            (string) (
                $invoice['id']
                ?? ''
            )
        );

        if ($externalInvoiceId === '') {
            return;
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
    }

    private function processInvoicePaid(
    array $invoice
): void {
    $stripeSubscriptionId = trim(
        (string) (
            $invoice['subscription']
            ?? ''
        )
    );

    if ($stripeSubscriptionId === '') {
        return;
    }

    $subscription =
        Subscription::withoutGlobalScopes()
            ->where(
                'payment_provider',
                'stripe'
            )
            ->where(
                'external_reference',
                $stripeSubscriptionId
            )
            ->first();

    if ($subscription === null) {
        return;
    }

    $this->syncInvoice(
        $subscription,
        $invoice
    );

    $periodStart = data_get(
        $invoice,
        'lines.data.0.period.start'
    );

    $periodEnd = data_get(
        $invoice,
        'lines.data.0.period.end'
    );

    if (
        ! is_numeric($periodStart)
        || ! is_numeric($periodEnd)
    ) {
        return;
    }

if (
    $subscription->status ===
    \App\Enums\SubscriptionStatus::SUSPENDED
) {
    $subscription->forceFill([
        'status' =>
            \App\Enums\SubscriptionStatus::ACTIVE,
    ]);
}

    $subscription->forceFill([
        'current_period_start' =>
            CarbonImmutable::createFromTimestampUTC(
                (int) $periodStart
            ),

        'current_period_end' =>
            CarbonImmutable::createFromTimestampUTC(
                (int) $periodEnd
            ),

        'paid_at' =>
            CarbonImmutable::now('UTC'),
    ])->save();
}


private function processInvoicePaymentFailed(
    array $invoice
): void {
    $stripeSubscriptionId = trim(
        (string) (
            $invoice['subscription']
            ?? ''
        )
    );

    if ($stripeSubscriptionId === '') {
        return;
    }

    $subscription =
        Subscription::withoutGlobalScopes()
            ->where(
                'payment_provider',
                'stripe'
            )
            ->where(
                'external_reference',
                $stripeSubscriptionId
            )
            ->first();

    if ($subscription === null) {
        return;
    }

    $this->syncInvoice(
        $subscription,
        $invoice
    );

    if (
        $subscription->status ===
        \App\Enums\SubscriptionStatus::ACTIVE
    ) {
        $subscription->forceFill([
            'status' =>
                \App\Enums\SubscriptionStatus::SUSPENDED,
        ])->save();
    }
}

private function processSubscriptionDeleted(
    array $stripeSubscription
): void {
    $stripeSubscriptionId = trim(
        (string) (
            $stripeSubscription['id']
            ?? ''
        )
    );

    if ($stripeSubscriptionId === '') {
        return;
    }

    $subscription =
        Subscription::withoutGlobalScopes()
            ->where(
                'payment_provider',
                'stripe'
            )
            ->where(
                'external_reference',
                $stripeSubscriptionId
            )
            ->first();

    if ($subscription === null) {
        return;
    }

    if (
        $subscription->status ===
        \App\Enums\SubscriptionStatus::CANCELLED
    ) {
        return;
    }

    $canceledAt =
        $stripeSubscription['canceled_at']
        ?? null;

    $subscription->forceFill([
        'status' =>
            \App\Enums\SubscriptionStatus::CANCELLED,
        'cancel_at' => null,
        'canceled_at' =>
            is_numeric($canceledAt)
                ? CarbonImmutable::createFromTimestampUTC(
                    (int) $canceledAt
                )
                : ($subscription->canceled_at
                    ?? CarbonImmutable::now('UTC')),
    ])->save();
}

private function processSubscriptionUpdated(
    array $stripeSubscription
): void {
    $stripeSubscriptionId = trim(
        (string) (
            $stripeSubscription['id']
            ?? ''
        )
    );

    $stripeStatus = trim(
        (string) (
            $stripeSubscription['status']
            ?? ''
        )
    );

    if (
        $stripeSubscriptionId === ''
        || $stripeStatus === ''
    ) {
        return;
    }

    $subscription =
        Subscription::withoutGlobalScopes()
            ->where(
                'payment_provider',
                'stripe'
            )
            ->where(
                'external_reference',
                $stripeSubscriptionId
            )
            ->first();

    if ($subscription === null) {
        return;
    }

    $cancelAt =
        $stripeSubscription['cancel_at']
        ?? null;

    if (
        ! is_numeric($cancelAt)
        && ($stripeSubscription['cancel_at_period_end'] ?? false) === true
    ) {
        $periodEnd =
            $stripeSubscription['current_period_end']
            ?? null;

        if (is_numeric($periodEnd)) {
            $cancelAt = $periodEnd;
        }
    }

    $canceledAt =
        $stripeSubscription['canceled_at']
        ?? null;

    $localStatus = match ($stripeStatus) {
        'active',
        'trialing' =>
            \App\Enums\SubscriptionStatus::ACTIVE,

        'past_due',
        'unpaid',
        'paused' =>
            \App\Enums\SubscriptionStatus::SUSPENDED,

        'canceled',
        'incomplete_expired' =>
            \App\Enums\SubscriptionStatus::CANCELLED,

        default => null,
    };

    $updates = [
        'cancel_at' =>
            is_numeric($cancelAt)
                ? CarbonImmutable::createFromTimestampUTC(
                    (int) $cancelAt
                )
                : null,

        'canceled_at' =>
            is_numeric($canceledAt)
                ? CarbonImmutable::createFromTimestampUTC(
                    (int) $canceledAt
                )
                : null,
    ];

    if ($localStatus !== null) {
        $updates['status'] = $localStatus;
    }

    $subscription
        ->forceFill($updates)
        ->save();
}

    private function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        $parts = [];

        foreach (
            explode(',', $signature)
            as $part
        ) {
            [$key, $value] = array_pad(
                explode('=', trim($part), 2),
                2,
                null
            );

            if (
                is_string($key)
                && is_string($value)
            ) {
                $parts[$key][] = $value;
            }
        }

        $timestamp =
            $parts['t'][0] ?? null;

        $signatures =
            $parts['v1'] ?? [];

        if (
            ! is_string($timestamp)
            || $timestamp === ''
            || ! is_array($signatures)
            || $signatures === []
        ) {
            return false;
        }

        $signedPayload =
            $timestamp . '.' . $payload;

        $expected = hash_hmac(
            'sha256',
            $signedPayload,
            $secret
        );

        foreach ($signatures as $received) {
            if (
                is_string($received)
                && hash_equals(
                    $expected,
                    $received
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
