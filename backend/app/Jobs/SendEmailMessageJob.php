<?php

namespace App\Jobs;

use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\EmailDeliveryService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendEmailMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $emailMessageId
    ) {
    }

    public function backoff(): array
    {
        return [
            60,
            300,
        ];
    }

    public function handle(
        TenantContext $tenantContext,
        EmailDeliveryService $delivery
    ): void {
        $tenant = Tenant::query()->findOrFail(
            $this->tenantId
        );

        $tenantContext->set(
            $tenant
        );

        $message = EmailMessage::query()
            ->findOrFail(
                $this->emailMessageId
            );

        if (
            $message->status ===
            EmailMessageStatus::SENT
        ) {
            return;
        }

        if (
            $message->status ===
            EmailMessageStatus::PENDING
        ) {
            $delivery->send(
                $message
            );

            return;
        }

        if (
            $message->status ===
            EmailMessageStatus::FAILED
        ) {
            $delivery->retry(
                $message
            );

            return;
        }

        throw new RuntimeException(
            'Unsupported email message status.'
        );
    }
}