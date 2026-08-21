<?php

namespace App\Services;

use App\Contracts\PaymentWebhookNormalizer;
use App\Contracts\PaymentWebhookVerifier;
use App\Models\Charge;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentWebhookHttpService
{
    public function __construct(
        private readonly PaymentEventProcessor $processor,
    ) {
    }

    public function handle(
        Request $request,
        string $provider,
        PaymentWebhookVerifier $verifier,
        PaymentWebhookNormalizer $normalizer,
    ): Charge {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            throw new RuntimeException(
                'Payment provider code is required.'
            );
        }

        if (! $verifier->verify($request)) {
            throw new RuntimeException(
                'Invalid payment webhook signature.'
            );
        }

        $event = $normalizer->normalize(
            $request
        );

        return $this->processor->process(
            $event,
            $provider
        );
    }
}