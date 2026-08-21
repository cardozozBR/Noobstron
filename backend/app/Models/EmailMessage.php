<?php

namespace App\Models;

use App\Enums\EmailMessageStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class EmailMessage extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [        'conversation_id',

        'tenant_id',
        'to_email',
        'to_name',
        'subject',
        'body',
        'status',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailMessageStatus::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmailMessage $message): void {
            $message->to_email = strtolower(
                trim(
                    (string) $message->to_email
                )
            );

            if (
                $message->to_email === ''
                || filter_var(
                    $message->to_email,
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                throw new RuntimeException(
                    'Email message recipient is required and must be valid.'
                );
            }

            if ($message->to_name !== null) {
                $toName = trim(
                    (string) $message->to_name
                );

                $message->to_name =
                    $toName !== ''
                        ? $toName
                        : null;
            }

            $message->subject = trim(
                (string) $message->subject
            );

            if ($message->subject === '') {
                throw new RuntimeException(
                    'Email message subject is required.'
                );
            }

            $message->body = trim(
                (string) $message->body
            );

            if ($message->body === '') {
                throw new RuntimeException(
                    'Email message body is required.'
                );
            }

            if ($message->failure_reason !== null) {
                $failureReason = trim(
                    (string) $message->failure_reason
                );

                $message->failure_reason =
                    $failureReason !== ''
                        ? $failureReason
                        : null;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            Conversation::class
        );
    }
}
