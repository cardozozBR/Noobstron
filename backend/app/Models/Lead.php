<?php

namespace App\Models;

use App\Models\Customer;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'responsible_user_id',
        'name',
        'email',
        'phone',
        'status',
        'source',
        'tags',
        'notes',
        'converted_customer_id',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'tags' => 'array',
            'converted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsible_user_id'
        );
    }

    public function convertedCustomer()
    {
        return $this->belongsTo(
            Customer::class,
            'converted_customer_id'
        );
    }

    public function isConverted(): bool
    {
        return $this->converted_customer_id !== null;
    }
}
