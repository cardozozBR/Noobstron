<?php

namespace App\Models;

use App\Enums\CustomerContactType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'name',
        'role',
        'type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerContactType::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
