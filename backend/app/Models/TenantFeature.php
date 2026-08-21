<?php

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeature extends Model
{
    protected $fillable = [
        'tenant_id',
        'feature',
        'enabled',
        'limit_value',
    ];

    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'enabled' => 'boolean',
            'limit_value' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}