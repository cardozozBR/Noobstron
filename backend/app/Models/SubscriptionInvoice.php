<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'subscription_id',
        'provider',
        'external_invoice_id',
        'status',
        'currency',
        'amount_due',
        'amount_paid',
        'amount_remaining',
        'billing_reason',
        'period_start',
        'period_end',
        'paid_at',
        'hosted_invoice_url',
        'invoice_pdf',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
            'amount_remaining' => 'integer',
            'period_start' => 'immutable_datetime',
            'period_end' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            Subscription::class
        );
    }
}