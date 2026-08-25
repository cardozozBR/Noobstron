<?php

namespace App\Models;

use App\Enums\WhatsAppMessageStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class WhatsAppMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_messages';

    protected $fillable = [        'conversation_id',

        'phone',
        'recipient_name',
        'body',
        'status',
        'direction',
        'provider',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'received_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'status' =>
            WhatsAppMessageStatus::class,

        'sent_at' =>
            'datetime',

        'delivered_at' =>
            'datetime',

        'read_at' =>
            'datetime',

        'received_at' =>
            'datetime',

        'failed_at' =>
            'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (
                WhatsAppMessage $message
            ): void {
                $message->normalize();

                if (
                    blank(
                        $message->phone
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp phone is required.'
                    );
                }

                if (
                    blank(
                        $message->body
                    )
                ) {
                    throw new RuntimeException(
                        'WhatsApp message body is required.'
                    );
                }

                $message->status ??=
                    WhatsAppMessageStatus::PENDING;

                $message->direction ??=
                    'outbound';
            }
        );

        static::updating(
            function (
                WhatsAppMessage $message
            ): void {
                $message->normalize();
            }
        );
    }

    private function normalize(): void
    {
        if (
            is_string(
                $this->phone
            )
        ) {
            $this->phone =
                preg_replace(
                    '/\D+/',
                    '',
                    $this->phone
                );
        }

        if (
            is_string(
                $this->recipient_name
            )
        ) {
            $this->recipient_name =
                trim(
                    $this->recipient_name
                );

            if ($this->recipient_name === '') {
                $this->recipient_name = null;
            }
        }

        if (
            is_string(
                $this->body
            )
        ) {
            $this->body =
                trim(
                    $this->body
                );
        }

        if (
            is_string(
                $this->provider
            )
        ) {
            $this->provider =
                strtolower(
                    trim(
                        $this->provider
                    )
                );

            if ($this->provider === '') {
                $this->provider = null;
            }
        }

        if (
            is_string(
                $this->provider_message_id
            )
        ) {
            $this->provider_message_id =
                trim(
                    $this->provider_message_id
                );

            if (
                $this->provider_message_id === ''
            ) {
                $this->provider_message_id = null;
            }
        }

        if (
            is_string(
                $this->failure_reason
            )
        ) {
            $this->failure_reason =
                trim(
                    $this->failure_reason
                );

            if (
                $this->failure_reason === ''
            ) {
                $this->failure_reason = null;
            }
        }
    }

    public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            Conversation::class
        );
    }
}