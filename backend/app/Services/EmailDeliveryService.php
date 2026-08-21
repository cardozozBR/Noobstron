<?php

namespace App\Services;

use App\Enums\EmailMessageStatus;
use App\Mail\TenantEmailMessageMail;
use App\Models\EmailMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    public function __construct(
        private readonly EmailMessageService $messages
    ) {
    }

    public function send(
        EmailMessage $message
    ): EmailMessage {
        $message = $this->currentTenantMessage(
            $message
        );

        if (
            $message->status !==
            EmailMessageStatus::PENDING
        ) {
            throw new \RuntimeException(
                'Only pending email messages can be sent.'
            );
        }

        try {
            Mail::to([
                [
                    'email' =>
                        $message->to_email,

                    'name' =>
                        $message->to_name,
                ],
            ])->send(
                new TenantEmailMessageMail(
                    $message
                )
            );
        } catch (Throwable $exception) {
            $reason = trim(
                $exception->getMessage()
            );

            if ($reason === '') {
                $reason = get_class(
                    $exception
                );
            }

            $this->messages->markFailed(
                $message,
                $reason
            );

            throw $exception;
        }

        return $this->messages->markSent(
            $message
        );
    }

    public function retry(
        EmailMessage $message
    ): EmailMessage {
        $message = $this->currentTenantMessage(
            $message
        );

        if (
            $message->status !==
            EmailMessageStatus::FAILED
        ) {
            throw new \RuntimeException(
                'Only failed email messages can be retried.'
            );
        }

        $message = $this->messages->retry(
            $message
        );

        return $this->send(
            $message
        );
    }

    private function currentTenantMessage(
        EmailMessage $message
    ): EmailMessage {
        $current = EmailMessage::query()
            ->find(
                $message->getKey()
            );

        if ($current === null) {
            throw new ModelNotFoundException();
        }

        return $current;
    }
}