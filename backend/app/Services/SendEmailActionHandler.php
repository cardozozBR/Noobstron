<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class SendEmailActionHandler implements AutomationActionHandler
{
    public function __construct(
        private readonly EmailMessageService $messages,
        private readonly EmailQueueService $queue,
    ) {
    }

    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::SEND_EMAIL
        ) {
            throw new RuntimeException(
                'Invalid action type for send email handler.'
            );
        }

        $data = [];

        foreach ([
            'to_email',
            'to_name',
            'subject',
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

        $message =
            $this->queue->dispatch(
                $message
            );

        return AutomationActionResult::success([
            'email_message_id' =>
                $message->id,

            'status' =>
                $message->status->value,

            'queued' =>
                true,
        ]);
    }
}