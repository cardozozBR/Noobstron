<?php

namespace App\Models;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Conversation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'channel',
        'external_address',
        'display_name',
        'responsible_user_id',
        'lead_id',
        'customer_id',
        'status',
        'last_message_at',
        'closed_at',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'channel' =>
                ConversationChannel::class,

            'status' =>
                ConversationStatus::class,

            'last_message_at' =>
                'datetime',

            'closed_at' =>
                'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (
                Conversation $conversation
            ): void {
                $conversation->external_address =
                    trim(
                        (string) $conversation->external_address
                    );

                if ($conversation->external_address === '') {
                    throw new RuntimeException(
                        'Conversation external address is required.'
                    );
                }

                if (
                    $conversation->display_name !== null
                ) {
                    $name = trim(
                        (string) $conversation->display_name
                    );

                    $conversation->display_name =
                        $name !== ''
                            ? $name
                            : null;
                }

                if (
                    $conversation->channel ===
                    ConversationChannel::EMAIL
                ) {
                    $conversation->external_address =
                        strtolower(
                            $conversation->external_address
                        );

                    if (
                        filter_var(
                            $conversation->external_address,
                            FILTER_VALIDATE_EMAIL
                        ) === false
                    ) {
                        throw new RuntimeException(
                            'Email conversation address must be valid.'
                        );
                    }
                }

                if (
                    $conversation->channel ===
                    ConversationChannel::WHATSAPP
                ) {
                    $conversation->external_address =
                        preg_replace(
                            '/\D+/',
                            '',
                            $conversation->external_address
                        );

                    if (
                        blank(
                            $conversation->external_address
                        )
                    ) {
                        throw new RuntimeException(
                            'WhatsApp conversation phone is required.'
                        );
                    }
                }

                if (
                    $conversation->lead_id !== null
                    && $conversation->customer_id !== null
                ) {
                    throw new RuntimeException(
                        'Conversation cannot belong to both lead and customer.'
                    );
                }
            }
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsible_user_id'
        );
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(
            Lead::class
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function emailMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            EmailMessage::class
        );
    }

    public function whatsappMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            WhatsAppMessage::class
        );
    }
}