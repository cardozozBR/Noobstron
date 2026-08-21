<?php

namespace App\Services;

use App\Enums\ConversationChannel;
use App\Models\Conversation;
use App\Models\EmailMessage;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Collection;
use RuntimeException;

class ConversationHistoryService
{
    public function history(
        Conversation $conversation
    ): Collection {
        $this->assertCurrentTenant(
            $conversation
        );

        return match (
            $conversation->channel
        ) {
            ConversationChannel::EMAIL =>
                $this->emailHistory(
                    $conversation
                ),

            ConversationChannel::WHATSAPP =>
                $this->whatsAppHistory(
                    $conversation
                ),
        };
    }

    private function emailHistory(
        Conversation $conversation
    ): Collection {
        return $conversation
            ->emailMessages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    EmailMessage $message
                ): array =>
                    $this->normalizeEmail(
                        $message
                    )
            );
    }

    private function whatsAppHistory(
        Conversation $conversation
    ): Collection {
        return $conversation
            ->whatsappMessages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    WhatsAppMessage $message
                ): array =>
                    $this->normalizeWhatsApp(
                        $message
                    )
            );
    }

    private function normalizeEmail(
        EmailMessage $message
    ): array {
        return [
            'id' =>
                $message->id,

            'channel' =>
                ConversationChannel::EMAIL->value,

            'direction' =>
                'outbound',

            'address' =>
                $message->to_email,

            'name' =>
                $message->to_name,

            'subject' =>
                $message->subject,

            'body' =>
                $message->body,

            'status' =>
                $message->status->value,

            'provider' =>
                null,

            'provider_message_id' =>
                null,

            'created_at' =>
                $message->created_at,

            'sent_at' =>
                $message->sent_at,

            'received_at' =>
                null,

            'delivered_at' =>
                null,

            'read_at' =>
                null,

            'failed_at' =>
                $message->failed_at,

            'failure_reason' =>
                $message->failure_reason,

            'source' =>
                $message,
        ];
    }

    private function normalizeWhatsApp(
        WhatsAppMessage $message
    ): array {
        return [
            'id' =>
                $message->id,

            'channel' =>
                ConversationChannel::WHATSAPP->value,

            'direction' =>
                $message->direction,

            'address' =>
                $message->phone,

            'name' =>
                $message->recipient_name,

            'subject' =>
                null,

            'body' =>
                $message->body,

            'status' =>
                $message->status->value,

            'provider' =>
                $message->provider,

            'provider_message_id' =>
                $message->provider_message_id,

            'created_at' =>
                $message->created_at,

            'sent_at' =>
                $message->sent_at,

            'received_at' =>
                $message->received_at,

            'delivered_at' =>
                $message->delivered_at,

            'read_at' =>
                $message->read_at,

            'failed_at' =>
                $message->failed_at,

            'failure_reason' =>
                $message->failure_reason,

            'source' =>
                $message,
        ];
    }

    private function assertCurrentTenant(
        Conversation $conversation
    ): void {
        $tenant = app(
            TenantContext::class
        )->get();

        if (
            (int) $conversation->tenant_id !==
            (int) $tenant->id
        ) {
            throw new RuntimeException(
                'Conversation does not belong to current tenant.'
            );
        }
    }
}