<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Activity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'title',
        'description',
        'customer_id',
        'opportunity_id',
        'responsible_user_id',
        'due_at',
        'completed_at',
        'reminder_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'status' => ActivityStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Activity $activity): void {
            $activity->title = trim(
                (string) $activity->title
            );

            if ($activity->title === '') {
                throw new RuntimeException(
                    'Activity title is required.'
                );
            }

            if (
                $activity->customer_id !== null
                && Customer::query()
                    ->find($activity->customer_id) === null
            ) {
                throw new RuntimeException(
                    'Customer does not belong to current tenant.'
                );
            }

            if (
                $activity->opportunity_id !== null
                && Opportunity::query()
                    ->find($activity->opportunity_id) === null
            ) {
                throw new RuntimeException(
                    'Opportunity does not belong to current tenant.'
                );
            }

            if (
                $activity->responsible_user_id !== null
                && User::query()
                    ->find($activity->responsible_user_id) === null
            ) {
                throw new RuntimeException(
                    'Responsible user does not belong to current tenant.'
                );
            }

            if (
                $activity->status === ActivityStatus::COMPLETED
                && $activity->completed_at === null
            ) {
                $activity->completed_at = now();
            }

            if (
                $activity->status !== ActivityStatus::COMPLETED
            ) {
                $activity->completed_at = null;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsible_user_id'
        );
    }
}
