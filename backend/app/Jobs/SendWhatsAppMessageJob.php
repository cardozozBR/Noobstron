<?php

namespace App\Jobs;

use App\Enums\WhatsAppMessageStatus;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use App\Services\WhatsAppDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $messageId,
        public readonly string $provider
    ) {
    }

    public function handle(
        TenantContext $tenantContext,
        WhatsAppDeliveryService $delivery
    ): void {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->findOrFail(
                $this->tenantId
            );

        $tenantContext->set(
            $tenant
        );

        $message = WhatsAppMessage::query()
            ->findOrFail(
                $this->messageId
            );

        if (
            $message->status !==
            WhatsAppMessageStatus::PENDING
        ) {
            return;
        }

        $delivery->send(
            $message,
            $this->provider
        );
    }

    public function failed(
        \Throwable $exception
    ): void {
        app(
            TenantContext::class
        )->clear();
    }
}