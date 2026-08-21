<?php

namespace App\Services;

use App\Contracts\WhatsAppWebhookNormalizer;
use App\Contracts\WhatsAppWebhookVerifier;
use Illuminate\Http\Request;
use RuntimeException;

class WhatsAppWebhookHttpService
{
    public function __construct(
        private readonly WhatsAppWebhookService $webhooks
    ) {
    }

    public function handle(
        Request $request,
        WhatsAppWebhookVerifier $verifier,
        WhatsAppWebhookNormalizer $normalizer
    ): int {
        if (
            ! $verifier->verify(
                $request
            )
        ) {
            throw new RuntimeException(
                'Invalid WhatsApp webhook signature.'
            );
        }

        $events = $normalizer
            ->normalize(
                $request
            );

        $processed = 0;

        foreach ($events as $event) {
            $this->webhooks
                ->handle(
                    $event
                );

            $processed++;
        }

        return $processed;
    }
}