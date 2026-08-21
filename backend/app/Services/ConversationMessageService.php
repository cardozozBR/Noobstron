<?php

namespace App\Services;

use App\Enums\ConversationChannel;
use App\Models\Conversation;
use App\Models\EmailMessage;
use App\Models\WhatsAppMessage;
use RuntimeException;

class ConversationMessageService
{
    public function attachEmail(
        Conversation $conversation,
        EmailMessage $message
    ): EmailMessage {
        $this->assertCurrentTenant(
            $conversation->tenant_id
        );

        $this->assertCurrentTenant(
            $message->tenant_id
        );

        if (
            $conversation->tenant_id !==
            $message->tenant_id
        ) {
            throw new RuntimeException(
                'Conversation and email message must belong to the same tenant.'
            );
        }

        if (
            $conversation->channel !==
            ConversationChannel::EMAIL
        ) {
            throw new RuntimeException(
                'Email message requires an email conversation.'
            );
        }

        if (
            strtolower(
                trim(
                    $message->to_email
                )
            ) !==
            $conversation->external_address
        ) {
            throw new RuntimeException(
                'Email recipient does not match conversation address.'
            );
        }

        $message->forceFill([
            'conversation_id' =>
                $conversation->id,
        ])->save();

        app(
            ConversationService::class
        )->touchLastMessage(
            $conversation,
            $message->created_at
        );

        return $message->refresh();
    }

    public function attachWhatsApp(
        Conversation $conversation,
        WhatsAppMessage $message
    ): WhatsAppMessage {
        $this->assertCurrentTenant(
            $conversation->tenant_id
        );

        $this->assertCurrentTenant(
            $message->tenant_id
        );

        if (
            $conversation->tenant_id !==
            $message->tenant_id
        ) {
            throw new RuntimeException(
                'Conversation and WhatsApp message must belong to the same tenant.'
            );
        }

        if (
            $conversation->channel !==
            ConversationChannel::WHATSAPP
        ) {
            throw new RuntimeException(
                'WhatsApp message requires a WhatsApp conversation.'
            );
        }

        if (
            $message->phone !==
            $conversation->external_address
        ) {
            throw new RuntimeException(
                'WhatsApp phone does not match conversation address.'
            );
        }

        $message->forceFill([
            'conversation_id' =>
                $conversation->id,
        ])->save();

        app(
            ConversationService::class
        )->touchLastMessage(
            $conversation,
            $message->created_at
        );

        return $message->refresh();
    }

    private function assertCurrentTenant(
        int $tenantId
    ): void {
        $tenant = app(
            TenantContext::class
        )->get();

        if (
            $tenantId !==
            (int) $tenant->id
        ) {
            throw new RuntimeException(
                'Communication record does not belong to current tenant.'
            );
        }
    }
}