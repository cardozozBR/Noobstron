<?php

namespace App\Services;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConversationService
{
    public function create(
        array $attributes
    ): Conversation {
        return Conversation::query()
            ->create(
                $attributes
            );
    }

    public function resolve(
        ConversationChannel $channel,
        string $externalAddress,
        ?string $displayName = null
    ): Conversation {
        $normalizedAddress =
            $this->normalizeAddress(
                $channel,
                $externalAddress
            );

        $conversation = Conversation::query()
            ->where(
                'channel',
                $channel->value
            )
            ->where(
                'external_address',
                $normalizedAddress
            )
            ->first();

        if ($conversation) {
            if (
                $displayName !== null
                && trim($displayName) !== ''
                && $conversation->display_name === null
            ) {
                $conversation->forceFill([
                    'display_name' =>
                        trim($displayName),
                ])->save();

                $conversation->refresh();
            }

            return $conversation;
        }

        return $this->create([
            'channel' =>
                $channel,

            'external_address' =>
                $normalizedAddress,

            'display_name' =>
                $displayName,
        ]);
    }

    public function assign(
        Conversation $conversation,
        ?User $user
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if ($user !== null) {
            $this->assertRelatedTenant(
                (int) $user->tenant_id
            );
        }

        $conversation->forceFill([
            'responsible_user_id' =>
                $user?->id,
        ])->save();

        return $conversation->refresh();
    }

    public function associateLead(
        Conversation $conversation,
        ?Lead $lead
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if ($lead !== null) {
            $this->assertRelatedTenant(
                (int) $lead->tenant_id
            );
        }

        $conversation->forceFill([
            'lead_id' =>
                $lead?->id,

            'customer_id' =>
                null,
        ])->save();

        return $conversation->refresh();
    }

    public function associateCustomer(
        Conversation $conversation,
        ?Customer $customer
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if ($customer !== null) {
            $this->assertRelatedTenant(
                (int) $customer->tenant_id
            );
        }

        $conversation->forceFill([
            'customer_id' =>
                $customer?->id,

            'lead_id' =>
                null,
        ])->save();

        return $conversation->refresh();
    }

    public function markPending(
        Conversation $conversation
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if (
            $conversation->status !==
            ConversationStatus::OPEN
        ) {
            throw new RuntimeException(
                'Only open conversations can become pending.'
            );
        }

        $conversation->forceFill([
            'status' =>
                ConversationStatus::PENDING,

            'closed_at' =>
                null,
        ])->save();

        return $conversation->refresh();
    }

    public function reopen(
        Conversation $conversation
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if (
            ! in_array(
                $conversation->status,
                [
                    ConversationStatus::PENDING,
                    ConversationStatus::CLOSED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only pending or closed conversations can be reopened.'
            );
        }

        $conversation->forceFill([
            'status' =>
                ConversationStatus::OPEN,

            'closed_at' =>
                null,
        ])->save();

        return $conversation->refresh();
    }

    public function close(
        Conversation $conversation
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        if (
            $conversation->status ===
            ConversationStatus::CLOSED
        ) {
            throw new RuntimeException(
                'Conversation is already closed.'
            );
        }

        $conversation->forceFill([
            'status' =>
                ConversationStatus::CLOSED,

            'closed_at' =>
                now(),
        ])->save();

        return $conversation->refresh();
    }

    public function touchLastMessage(
        Conversation $conversation,
        ?\DateTimeInterface $at = null
    ): Conversation {
        $this->assertCurrentTenant(
            $conversation
        );

        return DB::transaction(
            function () use (
                $conversation,
                $at
            ): Conversation {
                $conversation->forceFill([
                    'last_message_at' =>
                        $at ?? now(),
                ])->save();

                return $conversation->refresh();
            }
        );
    }

    private function normalizeAddress(
        ConversationChannel $channel,
        string $address
    ): string {
        $address = trim(
            $address
        );

        if ($channel === ConversationChannel::EMAIL) {
            $address = strtolower(
                $address
            );
        }

        if ($channel === ConversationChannel::WHATSAPP) {
            $address = preg_replace(
                '/\D+/',
                '',
                $address
            );
        }

        if ($address === '') {
            throw new RuntimeException(
                'Conversation external address is required.'
            );
        }

        return $address;
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

    private function assertRelatedTenant(
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
                'Related model does not belong to current tenant.'
            );
        }
    }
}