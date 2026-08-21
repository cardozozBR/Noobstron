<?php

namespace App\Services;

use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Throwable;

class MercadoPagoSubscriptionWebhookService
{
    public function __construct(
        private readonly SubscriptionBillingService $billing,
    ) {
    }

    public function processAuthorizedPayment(
        string $authorizedPaymentId
    ): bool {
        $authorizedPaymentId = trim(
            $authorizedPaymentId
        );

        if ($authorizedPaymentId === '') {
            return false;
        }

        $accessToken = trim(
            (string) config(
                'services.mercado_pago.access_token'
            )
        );

        if ($accessToken === '') {
            return false;
        }

        $baseUrl = rtrim(
            (string) config(
                'services.mercado_pago.base_url',
                'https://api.mercadopago.com'
            ),
            '/'
        );

        try {
            $response = Http::acceptJson()
                ->withToken($accessToken)
                ->timeout(15)
                ->get(
                    $baseUrl
                    . '/authorized_payments/'
                    . rawurlencode(
                        $authorizedPaymentId
                    )
                );
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $preapprovalId = trim(
            (string) $response->json(
                'preapproval_id'
            )
        );

        $paymentStatus = strtolower(
            trim(
                (string) $response->json(
                    'payment.status'
                )
            )
        );

        if (
            $preapprovalId === ''
            || $paymentStatus !== 'approved'
        ) {
            return false;
        }

        $subscription = Subscription::query()
            ->where(
                'payment_provider',
                'mercado_pago'
            )
            ->where(
                'external_reference',
                $preapprovalId
            )
            ->first();

        if ($subscription === null) {
            return false;
        }

        if ($this->billing->isPaid(
            $subscription
        )) {
            return true;
        }

        $this->billing->markPaid(
            $subscription,
            'mercado_pago',
            $preapprovalId,
            null,
            CarbonImmutable::now('UTC'),
        );

        return true;
  
        }

public function processPreapproval(
    string $preapprovalId
): bool {
    $preapprovalId = trim($preapprovalId);

    if ($preapprovalId === '') {
        return false;
    }

    $accessToken = trim(
        (string) config(
            'services.mercado_pago.access_token'
        )
    );

    if ($accessToken === '') {
        return false;
    }

    $baseUrl = rtrim(
        (string) config(
            'services.mercado_pago.base_url',
            'https://api.mercadopago.com'
        ),
        '/'
    );

    try {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get(
                $baseUrl
                . '/preapproval/'
                . rawurlencode($preapprovalId)
            );
    } catch (Throwable) {
        return false;
    }

    if (! $response->successful()) {
        return false;
    }

    $providerStatus = strtolower(
        trim(
            (string) $response->json('status')
        )
    );

    $externalReference = trim(
        (string) $response->json(
            'external_reference'
        )
    );

    $subscription = Subscription::query()
        ->where(
            'payment_provider',
            'mercado_pago'
        )
        ->where(
            'external_reference',
            $preapprovalId
        )
        ->first();

    if (
        $subscription === null
        && preg_match(
            '/^subscription-(\d+)$/',
            $externalReference,
            $matches
        )
    ) {
        $subscription = Subscription::query()
            ->find((int) $matches[1]);
    }

    if ($subscription === null) {
        return false;
    }

    $status = match ($providerStatus) {
        'authorized' =>
            \App\Enums\SubscriptionStatus::ACTIVE,

        'paused' =>
            \App\Enums\SubscriptionStatus::SUSPENDED,

        'cancelled' =>
            \App\Enums\SubscriptionStatus::CANCELLED,

        default => null,
    };

    if ($status === null) {
        return true;
    }

    $subscription->forceFill([
        'status' => $status,
        'payment_provider' => 'mercado_pago',
        'external_reference' => $preapprovalId,
    ])->save();

    return true;
}

}