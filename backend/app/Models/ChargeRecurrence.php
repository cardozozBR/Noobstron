<?php

namespace App\Models;

use App\Enums\ChargeRecurrenceFrequency;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class ChargeRecurrence extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'frequency' => 'monthly',
        'interval_count' => 1,
        'is_active' => true,
    ];

    protected $fillable = [
        'tenant_id',
        'receivable_id',
        'frequency',
        'interval_count',
        'next_run_at',
        'ends_at',
        'is_active',
        'channel',
        'recipient',
    ];

    protected function casts(): array
    {
        return [
            'frequency' =>
                ChargeRecurrenceFrequency::class,
            'interval_count' => 'integer',
            'next_run_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (
                ChargeRecurrence $recurrence
            ): void {
                if (
                    (int) $recurrence->interval_count
                    < 1
                ) {
                    throw new RuntimeException(
                        'Charge recurrence interval must be positive.'
                    );
                }

                foreach ([
                    'channel',
                    'recipient',
                ] as $field) {
                    if (
                        $recurrence->{$field}
                        === null
                    ) {
                        continue;
                    }

                    $value = trim(
                        (string) $recurrence->{$field}
                    );

                    $recurrence->{$field} =
                        $value !== ''
                            ? $value
                            : null;
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

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(
            Receivable::class
        );
    }
}