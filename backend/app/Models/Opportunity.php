<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Opportunity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'customer_id',
        'pipeline_id',
        'pipeline_stage_id',
        'responsible_user_id',
        'value_minor',
        'currency',
        'probability',
        'expected_close_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value_minor' => 'integer',
            'probability' => 'integer',
            'expected_close_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Opportunity $opportunity): void {
            $opportunity->name = trim(
                (string) $opportunity->name
            );

            if ($opportunity->name === '') {
                throw new RuntimeException(
                    'Opportunity name is required.'
                );
            }

            $opportunity->currency = strtoupper(
                trim(
                    (string) $opportunity->currency
                )
            );

            if (
                strlen($opportunity->currency) !== 3
            ) {
                throw new RuntimeException(
                    'Opportunity currency is invalid.'
                );
            }

            if (
                (int) $opportunity->value_minor < 0
            ) {
                throw new RuntimeException(
                    'Opportunity value cannot be negative.'
                );
            }

            if (
                (int) $opportunity->probability < 0
                || (int) $opportunity->probability > 100
            ) {
                throw new RuntimeException(
                    'Opportunity probability must be between 0 and 100.'
                );
            }

            $customer = Customer::query()
                ->find($opportunity->customer_id);

            if ($customer === null) {
                throw new RuntimeException(
                    'Customer does not belong to current tenant.'
                );
            }

            $pipeline = Pipeline::query()
                ->find($opportunity->pipeline_id);

            if ($pipeline === null) {
                throw new RuntimeException(
                    'Pipeline does not belong to current tenant.'
                );
            }

            $stage = PipelineStage::query()
                ->find($opportunity->pipeline_stage_id);

            if ($stage === null) {
                throw new RuntimeException(
                    'Pipeline stage does not belong to current tenant.'
                );
            }

            if (
                (int) $stage->pipeline_id
                !== (int) $pipeline->id
            ) {
                throw new RuntimeException(
                    'Pipeline stage does not belong to selected pipeline.'
                );
            }

            if (
                $opportunity->responsible_user_id !== null
                && User::query()
                    ->find(
                        $opportunity->responsible_user_id
                    ) === null
            ) {
                throw new RuntimeException(
                    'Responsible user does not belong to current tenant.'
                );
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(
            Pipeline::class
        );
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(
            PipelineStage::class,
            'pipeline_stage_id'
        );
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsible_user_id'
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
}
