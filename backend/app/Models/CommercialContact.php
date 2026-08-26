<?php

namespace App\Models;

use App\Enums\CommercialContactStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'email',
    'company',
    'message',
    'locale',
    'ip_address',
    'user_agent',
    'status',
    'converted_tenant_id',
    'converted_at',
])]
class CommercialContact extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CommercialContactStatus::class,
            'converted_at' => 'datetime',
        ];
    }

    public function convertedTenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'converted_tenant_id'
        );
    }
}
