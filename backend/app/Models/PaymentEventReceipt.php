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
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'immutable_datetime',
        ];
    }
}