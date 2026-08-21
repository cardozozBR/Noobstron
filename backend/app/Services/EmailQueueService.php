<?php

namespace App\Services;

use App\Enums\EmailMessageStatus;
use App\Jobs\SendEmailMessageJob;
use App\Models\EmailMessage;
use RuntimeException;

class EmailQueueService
{
    public function dispatch(
        EmailMessage $message
    ): EmailMessage {
        $message = $this->currentTenantMessage(
            $message
        );

        if (
            ! in_array(
                $message->status,
                [
                    EmailMessageStatus::PENDING,
                    EmailMessageStatus::FAILED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only pending or failed email messages can be queued.'
            );
        }

        SendEmailMessageJob::dispatch(
            $message->tenant_id,
            $message->id
        );

        return $message;
    }

    private function currentTenantMessage(
        EmailMessage $message
    ): EmailMessage {
        return EmailMessage::query()
            ->findOrFail(
                $message->getKey()
            );
    }
}