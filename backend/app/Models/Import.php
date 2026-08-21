<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportTarget;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'target',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
        'status',
        'delimiter',
        'encoding',
        'header',
        'mapping',
        'row_count',
        'processed_count',
        'success_count',
        'failure_count',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target' => ImportTarget::class,
            'status' => ImportStatus::class,
            'header' => 'array',
            'mapping' => 'array',
            'row_count' => 'integer',
            'processed_count' => 'integer',
            'success_count' => 'integer',
            'failure_count' => 'integer',
            'size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function rows(): HasMany
    {
        return $this->hasMany(
            ImportRow::class
        );
    }
}
