<?php

namespace App\Services;

use App\Enums\WhatsAppMessageStatus;
use App\Models\WhatsAppMessage;
use Throwable;

class WhatsAppDeliveryService
{
    public function __construct(
        private readonly WhatsAppProviderRegistry $providers,
        private readonly WhatsAppProviderConfigService $configs,
        private readonly WhatsAppMessageService $messages
    ) {
    }

    public function send(
        WhatsAppMessage $message,
        string $provider
    ): WhatsAppMessage {
        if (
            $message->status !==
            WhatsAppMessageStatus::PENDING
        ) {
            throw new \RuntimeException(
                'Only pending WhatsApp messages can be delivered.'
            );
        }

        $config = $this->configs
            ->active(
                $provider
            );

        $providerInstance = $this->providers
            ->get(
                $config->provider
            );

        try {
            $result = $providerInstance
                ->send(
                    $message
                );

            return $this->messages
                ->markSent(
                    $message,
                    $result->provider,
                    $result->messageId
                );
        }
        catch (Throwable $exception) {
            return $this->messages
                ->markFailed(
                    $message,
                    $exception->getMessage()
                );
        }
    }
}