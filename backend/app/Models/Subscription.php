<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'payment_provider',
        'external_reference',
        'payment_method',
        'paid_at',
        'current_period_start',
        'current_period_end',
        'cancel_at',
        'canceled_at',
        'currency',
        'amount_minor',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'paid_at' => 'immutable_datetime',
            'current_period_start' => 'immutable_datetime',
            'current_period_end' => 'immutable_datetime',
            'cancel_at' => 'immutable_datetime',
            'canceled_at' => 'immutable_datetime',
            'amount_minor' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class
        );
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(
            SubscriptionInvoice::class
        );
    }
}