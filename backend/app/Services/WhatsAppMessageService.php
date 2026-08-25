<?php

namespace App\Services;

use App\Enums\ConversationChannel;
use App\Enums\UsageMetric;
use App\Enums\WhatsAppMessageStatus;
use App\Exceptions\UsageBlockedException;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WhatsAppMessageService
{
    public function create(
        array $attributes
    ): WhatsAppMessage {
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
        } catch (UsageBlockedException $exception) {
            if ($exception->reason !== 'unavailable') {
                throw $exception;
            }
        }

        $message = WhatsAppMessage::query()
            ->create(
                $attributes
            );

        $conversation = app(
            ConversationService::class
        )->resolve(
            ConversationChannel::WHATSAPP,
            $message->phone,
            $message->recipient_name
        );

        $message = app(
            ConversationMessageService::class
        )->attachWhatsApp(
            $conversation,
            $message
        );
        app(AuditService::class)->log(
            'whatsapp.created',
            'Mensagem WhatsApp criada para '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    public function markSent(
        WhatsAppMessage $message,
        ?string $provider = null,
        ?string $providerMessageId = null
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $message
        );

        if (
            $message->status !==
            WhatsAppMessageStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending WhatsApp messages can be marked as sent.'
            );
        }

        $message = DB::transaction(
            function () use (
                $message,
                $provider,
                $providerMessageId
            ): WhatsAppMessage {
                $message->forceFill([
                    'status' => WhatsAppMessageStatus::SENT,

                    'provider' => $provider,

                    'provider_message_id' => $providerMessageId,

                    'sent_at' => now(),

                    'failed_at' => null,

                    'failure_reason' => null,
                ])->save();

                return $message->refresh();
            }
        );

        app(AuditService::class)->log(
            'whatsapp.sent',
            'Mensagem WhatsApp enviada para '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    public function markDelivered(
        WhatsAppMessage $message
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $message
        );

        if (
            $message->status !==
            WhatsAppMessageStatus::SENT
        ) {
            throw new RuntimeException(
                'Only sent WhatsApp messages can be marked as delivered.'
            );
        }

        $message = DB::transaction(
            function () use (
                $message
            ): WhatsAppMessage {
                $message->forceFill([
                    'status' => WhatsAppMessageStatus::DELIVERED,

                    'delivered_at' => now(),
                ])->save();

                return $message->refresh();
            }
        );

        app(AuditService::class)->log(
            'whatsapp.delivered',
            'Mensagem WhatsApp entregue para '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    public function markRead(
        WhatsAppMessage $message
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $message
        );

        if (
            $message->status !==
            WhatsAppMessageStatus::DELIVERED
        ) {
            throw new RuntimeException(
                'Only delivered WhatsApp messages can be marked as read.'
            );
        }

        $message = DB::transaction(
            function () use (
                $message
            ): WhatsAppMessage {
                $message->forceFill([
                    'status' => WhatsAppMessageStatus::READ,

                    'read_at' => now(),
                ])->save();

                return $message->refresh();
            }
        );

        app(AuditService::class)->log(
            'whatsapp.read',
            'Mensagem WhatsApp lida por '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    public function markFailed(
        WhatsAppMessage $message,
        string $reason
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $message
        );

        $reason = trim(
            $reason
        );

        if ($reason === '') {
            throw new RuntimeException(
                'WhatsApp failure reason is required.'
            );
        }

        if (
            ! in_array(
                $message->status,
                [
                    WhatsAppMessageStatus::PENDING,
                    WhatsAppMessageStatus::SENT,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'WhatsApp message cannot be marked as failed from its current state.'
            );
        }

        $message = DB::transaction(
            function () use (
                $message,
                $reason
            ): WhatsAppMessage {
                $message->forceFill([
                    'status' => WhatsAppMessageStatus::FAILED,

                    'failed_at' => now(),

                    'failure_reason' => $reason,
                ])->save();

                return $message->refresh();
            }
        );

        app(AuditService::class)->log(
            'whatsapp.failed',
            'Falha na mensagem WhatsApp para '
                .$message->phone
                .'. ID: '
                .$message->id
                .'. Motivo: '
                .$reason
                .'.'
        );

        return $message;
    }

    public function retry(
        WhatsAppMessage $message
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $message
        );

        if (
            $message->status !==
            WhatsAppMessageStatus::FAILED
        ) {
            throw new RuntimeException(
                'Only failed WhatsApp messages can be retried.'
            );
        }

        if (blank($message->provider)) {
            throw new RuntimeException(
                'WhatsApp provider is required to retry message.'
            );
        }

        $message = DB::transaction(
            function () use (
                $message
            ): WhatsAppMessage {
                $message->forceFill([
                    'status' => WhatsAppMessageStatus::PENDING,

                    'provider_message_id' => null,

                    'sent_at' => null,

                    'delivered_at' => null,

                    'read_at' => null,

                    'failed_at' => null,

                    'failure_reason' => null,
                ])->save();

                return $message->refresh();
            }
        );

        app(AuditService::class)->log(
            'whatsapp.retried',
            'Novo envio WhatsApp solicitado para '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    public function receive(
        array $attributes
    ): WhatsAppMessage {
        $attributes['status'] =
            WhatsAppMessageStatus::RECEIVED;

        $attributes['direction'] =
            'inbound';

        $attributes['received_at'] ??=
            now();

        $message = WhatsAppMessage::query()
            ->create(
                $attributes
            );

        $conversation = app(
            ConversationService::class
        )->resolve(
            ConversationChannel::WHATSAPP,
            $message->phone,
            $message->recipient_name
        );

        $message = app(
            ConversationMessageService::class
        )->attachWhatsApp(
            $conversation,
            $message
        );
        app(AuditService::class)->log(
            'whatsapp.received',
            'Mensagem WhatsApp recebida de '
                .$message->phone
                .'. ID: '
                .$message->id
                .'.'
        );

        return $message;
    }

    private function assertCurrentTenant(
        WhatsAppMessage $message
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
    }
}
