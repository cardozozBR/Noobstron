<?php

namespace App\Support;

use App\Enums\WhatsAppWebhookEventType;
use RuntimeException;

final readonly class WhatsAppWebhookEvent
{
    public function __construct(
        public WhatsAppWebhookEventType $type,
        public string $provider,
        public string $providerMessageId,
        public ?string $phone = null,
        public ?string $body = null,
        public ?string $recipientName = null,
        public ?string $failureReason = null
    ) {
        if (
            trim(
                $this->provider
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp webhook provider is required.'
            );
        }

        if (
            trim(
                $this->providerMessageId
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp webhook provider message id is required.'
            );
        }

        if (
            $this->type === WhatsAppWebhookEventType::RECEIVED
            && trim(
                (string) $this->phone
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp inbound phone is required.'
            );
        }

        if (
            $this->type === WhatsAppWebhookEventType::RECEIVED
            && trim(
                (string) $this->body
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp inbound body is required.'
            );
        }

        if (
            $this->type === WhatsAppWebhookEventType::FAILED
            && trim(
                (string) $this->failureReason
            ) === ''
        ) {
            throw new RuntimeException(
                'WhatsApp webhook failure reason is required.'
            );
        }
    }
}