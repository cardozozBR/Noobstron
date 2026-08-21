<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class SendWhatsAppActionHandler implements AutomationActionHandler
{
    public function __construct(
        private readonly WhatsAppMessageService $messages,
        private readonly WhatsAppQueueService $queue,
    ) {
    }

    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::SEND_WHATSAPP
        ) {
            throw new RuntimeException(
                'Invalid action type for send WhatsApp handler.'
            );
        }

        $provider = trim(
            (string) (
                $action->parameters['provider']
                ?? ''
            )
        );

        if ($provider === '') {
            throw new RuntimeException(
                'WhatsApp provider is required.'
            );
        }

        $data = [];

        foreach ([
            'phone',
            'recipient_name',
            'body',
        ] as $field) {
            if (
                array_key_exists(
                    $field,
                    $action->parameters
                )
            ) {
                $data[$field] =
                    $action->parameters[$field];
            }
        }

        $message =
            $this->messages->create(
                $data
            );

        $this->queue->dispatch(
            $message,
            $provider
        );

        return AutomationActionResult::success([
            'whatsapp_message_id' =>
                $message->id,

            'status' =>
                $message->status->value,

            'provider' =>
                strtolower($provider),

            'queued' =>
                true,
        ]);
    }
}