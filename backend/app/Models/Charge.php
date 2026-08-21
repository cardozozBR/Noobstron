<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Charge extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'pending',
        'attempt' => 1,
    ];

    protected $fillable = [
        'tenant_id',
        'receivable_id',
        'status',
        'attempt',
        'scheduled_at',
        'sent_at',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'channel',
        'recipient',
        'external_reference',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChargeStatus::class,
            'attempt' => 'integer',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Charge $charge): void {
            if ((int) $charge->attempt < 1) {
                throw new RuntimeException(
                    'Charge attempt must be positive.'
                );
            }

            foreach ([
                'channel',
                'recipient',
                'external_reference',
                'failure_reason',
            ] as $field) {
                if ($charge->{$field} === null) {
                    continue;
                }

                $value = trim(
                    (string) $charge->{$field}
                );

                $charge->{$field} =
                    $value !== ''
                        ? $value
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

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(
            Receivable::class
        );
    }
}