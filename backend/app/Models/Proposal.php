<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Support\Currency;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Proposal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'opportunity_id',
        'number',
        'status',
        'currency',
        'valid_until',
        'subtotal_minor',
        'discount_minor',
        'tax_minor',
        'total_minor',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'valid_until' => 'date',
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Proposal $proposal): void {
            $proposal->number = trim(
                (string) $proposal->number
            );

            if ($proposal->number === '') {
                throw new RuntimeException(
                    'Proposal number is required.'
                );
            }

            $proposal->currency = Currency::normalize(
                (string) $proposal->currency
            );

            foreach ([
                'subtotal_minor',
                'discount_minor',
                'tax_minor',
                'total_minor',
            ] as $field) {
                if ((int) $proposal->{$field} < 0) {
                    throw new RuntimeException(
                        'Proposal monetary values cannot be negative.'
                    );
                }
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(
            Opportunity::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            ProposalItem::class
        )->orderBy('position');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
