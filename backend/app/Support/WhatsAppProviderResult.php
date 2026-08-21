<?php

namespace App\Support;

use RuntimeException;

final readonly class WhatsAppProviderResult
{
    public function __construct(
        public string $provider,
        public string $messageId
    ) {
        if (
            trim(
                $this->provider
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp provider name is required.'
            );
        }

        if (
            trim(
                $this->messageId
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp provider message id is required.'
            );
        }
    }
}