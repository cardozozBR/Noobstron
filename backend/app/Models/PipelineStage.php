<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class PipelineStage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'pipeline_id',
        'name',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PipelineStage $stage): void {
            if ($stage->pipeline_id === null) {
                return;
            }

            $pipeline = Pipeline::query()
                ->find($stage->pipeline_id);

            if ($pipeline === null) {
                throw new RuntimeException(
                    'Pipeline does not belong to current tenant.'
                );
            }

            if (
                $stage->tenant_id !== null
                && (int) $stage->tenant_id !== (int) $pipeline->tenant_id
            ) {
                throw new RuntimeException(
                    'Pipeline stage tenant mismatch.'
                );
            }

            $stage->tenant_id = $pipeline->tenant_id;
        });
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Opportunity::class,
            'pipeline_stage_id'
        );
    }
}
