<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriggerOccurrenceRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'trigger_occurrences';

    protected $fillable = [
        'tenant_id',
        'trigger_name',
        'subject_type',
        'subject_id',
        'boundary',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }
}