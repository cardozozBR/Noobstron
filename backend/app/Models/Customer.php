<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'responsible_user_id',
        'type',
        'name',
        'legal_name',
        'tax_country_code',
        'tax_identifier_type',
        'tax_identifier',
        'tags',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'tags' => 'array',
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

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(CustomerEmail::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(CustomerHistory::class);
    }

    public function sourceLead()
    {
        return $this->hasOne(
            Lead::class,
            'converted_customer_id'
        );
    }

public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Opportunity::class
        );
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function proposals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Proposal::class
        );
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    public function receivables(): HasMany
    {
        return $this->hasMany(
            Receivable::class
        );
    }
}
