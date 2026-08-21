<?php

namespace App\Services;

use App\Enums\UsageMetric;

use App\Enums\ConversationChannel;
use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use Illuminate\Support\Carbon;
use RuntimeException;

class EmailMessageService
{
    public function create(
        array $data
    ): EmailMessage {
        $tenant = app(
            TenantContext::class
        )->get();

        try {
            app(
                TenantUsageGuard::class
            )->assertCanConsume(
                $tenant,
                UsageMetric::MESSAGES,
                1
            );
        } catch (\App\Exceptions\UsageBlockedException $exception) {
            if ($exception->reason !== 'unavailable') {
                throw $exception;
            }
        }

        $message = EmailMessage::query()->create([
            'to_email' =>
                $data['to_email'] ?? null,

            'to_name' =>
                $data['to_name'] ?? null,

            'subject' =>
                $data['subject'] ?? null,

            'body' =>
                $data['body'] ?? null,

            'status' =>
                EmailMessageStatus::PENDING,
        ]);

        $conversation = app(
            ConversationService::class
        )->resolve(
            ConversationChannel::EMAIL,
            $message->to_email,
            $message->to_name
        );

        $message = app(
            ConversationMessageService::class
        )->attachEmail(
            $conversation,
            $message
        );
        app(AuditService::class)->log(
            'email.created',
            'E-mail criado para '
                . $message->to_email
                . '. ID: '
                . $message->id
                . '.'
        );

        return $message;
    }

    public function markSent(
        EmailMessage $message,
        ?Carbon $sentAt = null
    ): EmailMessage {
        $message = $this->currentTenantMessage(
            $message
        );

        if (
            $message->status !==
            EmailMessageStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending email messages can be marked as sent.'
            );
        }

        $message->forceFill([
            'status' =>
                EmailMessageStatus::SENT,

            'sent_at' =>
                $sentAt ?? now(),

            'failed_at' =>
                null,

            'failure_reason' =>
                null,
        ])->save();

        app(AuditService::class)->log(
            'email.sent',
            'E-mail enviado para '
                . $message->to_email
                . '. ID: '
                . $message->id
                . '.'
        );

        return $message->refresh();
    }

    public function markFailed(
        EmailMessage $message,
        string $reason,
        ?Carbon $failedAt = null
    ): EmailMessage {
        $message = $this->currentTenantMessage(
            $message
        );

        if (
            $message->status !==
            EmailMessageStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending email messages can be marked as failed.'
            );
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException(
                'Failure reason is required.'
            );
        }

        $message->forceFill([
            'status' =>
                EmailMessageStatus::FAILED,

            'failed_at' =>
                $failedAt ?? now(),

            'failure_reason' =>
                $reason,

            'sent_at' =>
                null,
        ])->save();

        app(AuditService::class)->log(
            'email.failed',
            'Falha no envio do e-mail para '
                . $message->to_email
                . '. ID: '
                . $message->id
                . '. Motivo: '
                . $reason
                . '.'
        );

        return $message->refresh();
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
            throw new RuntimeException(
                'Only failed email messages can be retried.'
            );
        }

        $message->forceFill([
            'status' =>
                EmailMessageStatus::PENDING,

            'sent_at' =>
                null,

            'failed_at' =>
                null,

            'failure_reason' =>
                null,
        ])->save();

        app(AuditService::class)->log(
            'email.retried',
            'Novo envio solicitado para '
                . $message->to_email
                . '. ID: '
                . $message->id
                . '.'
        );

        return $message->refresh();
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