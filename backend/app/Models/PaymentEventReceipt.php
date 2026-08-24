<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEventReceipt extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'external_reference',
        'status',
        'attempts',
        'last_error',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'payload' => 'array',
            'processed_at' => 'immutable_datetime',
        ];
    }
}