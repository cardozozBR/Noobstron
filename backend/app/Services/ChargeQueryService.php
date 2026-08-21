<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ChargeQueryService
{
    public function scheduledUntil(
        Carbon $until
    ): Collection {
        return Charge::query()
            ->where(
                'status',
                ChargeStatus::PENDING->value
            )
            ->whereNotNull(
                'scheduled_at'
            )
            ->where(
                'scheduled_at',
                '<=',
                $until
            )
            ->orderBy(
                'scheduled_at'
            )
            ->get();
    }

    public function dueForReminder(
        ?Carbon $now = null
    ): Collection {
        $now ??= now();

        return Charge::query()
            ->where(
                'status',
                ChargeStatus::PENDING->value
            )
            ->whereNotNull(
                'scheduled_at'
            )
            ->where(
                'scheduled_at',
                '<=',
                $now
            )
            ->orderBy(
                'scheduled_at'
            )
            ->get();
    }

    public function overdue(
        ?Carbon $now = null
    ): Collection {
        $now ??= now();

        return Charge::query()
            ->whereHas(
                'receivable',
                function ($query) use ($now): void {
                    $query
                        ->where(
                            'status',
                            'pending'
                        )
                        ->whereDate(
                            'due_date',
                            '<',
                            $now->toDateString()
                        );
                }
            )
            ->whereNotIn(
                'status',
                [
                    ChargeStatus::PAID->value,
                    ChargeStatus::CANCELLED->value,
                ]
            )
            ->orderBy(
                'scheduled_at'
            )
            ->get();
    }

    public function upcoming(
        Carbon $from,
        Carbon $until
    ): Collection {
        return Charge::query()
            ->where(
                'status',
                ChargeStatus::PENDING->value
            )
            ->whereNotNull(
                'scheduled_at'
            )
            ->whereBetween(
                'scheduled_at',
                [
                    $from,
                    $until,
                ]
            )
            ->orderBy(
                'scheduled_at'
            )
            ->get();
    }
}