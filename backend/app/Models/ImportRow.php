<?php

namespace App\Models;

use App\Enums\ImportRowStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'import_id',
        'line',
        'status',
        'data',
        'errors',
        'entity_type',
        'entity_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportRowStatus::class,
            'data' => 'array',
            'errors' => 'array',
            'line' => 'integer',
            'entity_id' => 'integer',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(
            Import::class
        );
    }
}
