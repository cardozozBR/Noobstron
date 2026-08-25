<?php

namespace App\Services;

use App\Enums\WhatsAppMessageStatus;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppMessage;
use RuntimeException;

class WhatsAppQueueService
{
    public function dispatch(
        WhatsAppMessage $message,
        string $provider
    ): void {
        $tenant = app(
            TenantContext::class
        )->get();

        if (
            (int) $message->tenant_id !==
            (int) $tenant->id
        ) {
            throw new RuntimeException(
                'WhatsApp message does not belong to current tenant.'
            );
        }

        if (
            $message->status !==
            WhatsAppMessageStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending WhatsApp messages can be queued.'
            );
        }

        $provider = strtolower(
            trim(
                $provider
            )
        );

        if ($provider === '') {
            throw new RuntimeException(
                'WhatsApp provider is required.'
            );
        }

        $message->forceFill([
            'provider' => $provider,
        ])->save();

        SendWhatsAppMessageJob::dispatch(
            $tenant->id,
            $message->id,
            $provider
        );
    }
}
