<?php

namespace App\Models;

use App\Enums\ReceivableStatus;
use App\Support\Currency;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Receivable extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sale_id',
        'title',
        'currency',
        'amount_minor',
        'due_date',
        'status',
        'paid_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'due_date' => 'date',
            'status' => ReceivableStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Receivable $receivable): void {
            $receivable->title = trim(
                (string) $receivable->title
            );

            if ($receivable->title === '') {
                throw new RuntimeException(
                    'Receivable title is required.'
                );
            }

            $receivable->currency = Currency::normalize(
                (string) $receivable->currency
            );

            if (
                (int) $receivable->amount_minor < 0
            ) {
                throw new RuntimeException(
                    'Receivable amount cannot be negative.'
                );
            }

            if (
                $receivable->payment_reference !== null
            ) {
                $reference = trim(
                    (string) $receivable->payment_reference
                );

                $receivable->payment_reference =
                    $reference !== ''
                        ? $reference
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(
            Sale::class
        );
    }

    public function charges(): HasMany
    {
        return $this->hasMany(
            Charge::class
        );
    }

    public function chargeRecurrences(): HasMany
    {
        return $this->hasMany(
            ChargeRecurrence::class
        );
    }
}