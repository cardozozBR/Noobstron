<?php

namespace App\Services;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppWebhookEventType;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppWebhookEvent;
use RuntimeException;

class WhatsAppWebhookService
{
    public function __construct(
        private readonly WhatsAppMessageService $messages
    ) {
    }

    public function handle(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        return match (
            $event->type
        ) {
            WhatsAppWebhookEventType::RECEIVED =>
                $this->handleReceived(
                    $event
                ),

            WhatsAppWebhookEventType::DELIVERED =>
                $this->handleDelivered(
                    $event
                ),

            WhatsAppWebhookEventType::READ =>
                $this->handleRead(
                    $event
                ),

            WhatsAppWebhookEventType::FAILED =>
                $this->handleFailed(
                    $event
                ),
        };
    }

    private function handleReceived(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        $existing = $this->findExisting(
            $event
        );

        if ($existing) {
            if (
                $existing->status !==
                WhatsAppMessageStatus::RECEIVED
            ) {
                throw new RuntimeException(
                    'Existing WhatsApp message conflicts with inbound webhook.'
                );
            }

            return $existing;
        }

        return $this->messages
            ->receive([
                'phone' =>
                    $event->phone,

                'recipient_name' =>
                    $event->recipientName,

                'body' =>
                    $event->body,

                'provider' =>
                    $this->normalizeProvider(
                        $event->provider
                    ),

                'provider_message_id' =>
                    trim(
                        $event->providerMessageId
                    ),
            ]);
    }

    private function handleDelivered(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        $message = $this->findRequired(
            $event
        );

        if (
            in_array(
                $message->status,
                [
                    WhatsAppMessageStatus::DELIVERED,
                    WhatsAppMessageStatus::READ,
                ],
                true
            )
        ) {
            return $message;
        }

        return $this->messages
            ->markDelivered(
                $message
            );
    }

    private function handleRead(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        $message = $this->findRequired(
            $event
        );

        if (
            $message->status ===
            WhatsAppMessageStatus::READ
        ) {
            return $message;
        }

        if (
            $message->status ===
            WhatsAppMessageStatus::SENT
        ) {
            $message = $this->messages
                ->markDelivered(
                    $message
                );
        }

        return $this->messages
            ->markRead(
                $message
            );
    }

    private function handleFailed(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        $message = $this->findRequired(
            $event
        );

        if (
            $message->status ===
            WhatsAppMessageStatus::FAILED
        ) {
            return $message;
        }

        return $this->messages
            ->markFailed(
                $message,
                (string) $event->failureReason
            );
    }

    private function findRequired(
        WhatsAppWebhookEvent $event
    ): WhatsAppMessage {
        $message = $this->findExisting(
            $event
        );

        if (! $message) {
            throw new RuntimeException(
                'WhatsApp message for webhook event was not found.'
            );
        }

        return $message;
    }

    private function findExisting(
        WhatsAppWebhookEvent $event
    ): ?WhatsAppMessage {
        return WhatsAppMessage::query()
            ->where(
                'provider',
                $this->normalizeProvider(
                    $event->provider
                )
            )
            ->where(
                'provider_message_id',
                trim(
                    $event->providerMessageId
                )
            )
            ->first();
    }

    private function normalizeProvider(
        string $provider
    ): string {
        return strtolower(
            trim(
                $provider
            )
        );
    }
}