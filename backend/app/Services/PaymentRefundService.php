<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Support\PaymentProviderResult;
use RuntimeException;

class PaymentRefundService
{
    public function __construct(
        private readonly PaymentProviderRegistry $providers,
    ) {
    }

    public function refund(
        Charge $charge,
        string $providerCode,
    ): PaymentProviderResult {
        $charge->refresh();

        if ($charge->status !== ChargeStatus::PAID) {
            throw new RuntimeException(
                'Only paid charges can be refunded.'
            );
        }

        $provider = $this->providers->resolve(
            $providerCode
        );

        $result = $provider->refund($charge);

        if (! $result->successful) {
            throw new RuntimeException(
                $result->failureReason
                    ?? 'Payment refund failed.'
            );
        }

        app(AuditService::class)->log(
            'payment.refunded',
            'Pagamento reembolsado. Charge ID: '
                . $charge->id
                . '.'
        );

        return $result;
    }
}