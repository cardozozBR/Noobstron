<?php

namespace App\Services;

use App\Enums\ChargeRecurrenceFrequency;
use App\Enums\ReceivableStatus;
use App\Models\Charge;
use App\Models\ChargeRecurrence;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChargeRecurrenceService
{
    public function create(
        array $data
    ): ChargeRecurrence {
        $receivable = $this->resolveReceivable(
            $data['receivable_id'] ?? null
        );

        if (
            $receivable->status !==
            ReceivableStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending receivables can have charge recurrence.'
            );
        }

        $frequency =
            ChargeRecurrenceFrequency::from(
                (string) (
                    $data['frequency']
                    ?? ChargeRecurrenceFrequency::MONTHLY->value
                )
            );

        $interval = (int) (
            $data['interval_count']
            ?? 1
        );

        if ($interval < 1) {
            throw new RuntimeException(
                'Charge recurrence interval must be positive.'
            );
        }

        if (
            empty($data['next_run_at'])
        ) {
            throw new RuntimeException(
                'Charge recurrence next run is required.'
            );
        }

        $nextRun = Carbon::parse(
            $data['next_run_at']
        );

        $endsAt = ! empty(
            $data['ends_at']
        )
            ? Carbon::parse(
                $data['ends_at']
            )
            : null;

        if (
            $endsAt !== null
            &&
            $endsAt->lt($nextRun)
        ) {
            throw new RuntimeException(
                'Charge recurrence end must not precede next run.'
            );
        }

        return ChargeRecurrence::query()->create([
            'receivable_id' =>
                $receivable->id,
            'frequency' =>
                $frequency,
            'interval_count' =>
                $interval,
            'next_run_at' =>
                $nextRun,
            'ends_at' =>
                $endsAt,
            'is_active' => true,
            'channel' =>
                $data['channel']
                ?? null,
            'recipient' =>
                $data['recipient']
                ?? null,
        ]);
    }

    public function due(
        ?Carbon $now = null
    ): \Illuminate\Database\Eloquent\Collection {
        $now ??= now();

        return ChargeRecurrence::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                'next_run_at',
                '<=',
                $now
            )
            ->where(
                function ($query) use ($now): void {
                    $query
                        ->whereNull('ends_at')
                        ->orWhere(
                            'ends_at',
                            '>=',
                            $now
                        );
                }
            )
            ->orderBy(
                'next_run_at'
            )
            ->get();
    }

    public function processDue(
        ?Carbon $now = null
    ): array {
        $now ??= now();

        $charges = [];

        foreach (
            $this->due($now)
            as $recurrence
        ) {
            $charge = $this->process(
                $recurrence,
                $now
            );

            if ($charge !== null) {
                $charges[] = $charge;
            }
        }

        return $charges;
    }

    public function process(
        ChargeRecurrence $recurrence,
        ?Carbon $now = null
    ): ?Charge {
        $now ??= now();

        $recurrence =
            $this->resolveRecurrence(
                $recurrence
            );

        if (! $recurrence->is_active) {
            return null;
        }

        if (
            $recurrence->next_run_at->gt(
                $now
            )
        ) {
            return null;
        }

        if (
            $recurrence->ends_at !== null
            &&
            $recurrence->ends_at->lt(
                $now
            )
        ) {
            $recurrence->is_active = false;
            $recurrence->save();

            return null;
        }

        $receivable =
            $this->resolveReceivable(
                $recurrence->receivable_id
            );

        if (
            $receivable->status !==
            ReceivableStatus::PENDING
        ) {
            $recurrence->is_active = false;
            $recurrence->save();

            return null;
        }

        return DB::transaction(
            function () use (
                $recurrence,
                $now
            ): Charge {
                $charge = app(
                    ChargeService::class
                )->create([
                    'receivable_id' =>
                        $recurrence->receivable_id,
                    'scheduled_at' =>
                        $recurrence->next_run_at,
                    'channel' =>
                        $recurrence->channel,
                    'recipient' =>
                        $recurrence->recipient,
                ]);

                $next = $this->nextDate(
                    $recurrence->next_run_at,
                    $recurrence->frequency,
                    $recurrence->interval_count
                );

                if (
                    $recurrence->ends_at !== null
                    &&
                    $next->gt(
                        $recurrence->ends_at
                    )
                ) {
                    $recurrence->is_active =
                        false;
                }
                else {
                    $recurrence->next_run_at =
                        $next;
                }

                $recurrence->save();

                return $charge;
            }
        );
    }

    public function cancel(
        ChargeRecurrence $recurrence
    ): ChargeRecurrence {
        $recurrence =
            $this->resolveRecurrence(
                $recurrence
            );

        $recurrence->is_active =
            false;

        $recurrence->save();

        return $recurrence->refresh();
    }

    private function nextDate(
        Carbon $current,
        ChargeRecurrenceFrequency $frequency,
        int $interval
    ): Carbon {
        $next = $current->copy();

        return match ($frequency) {
            ChargeRecurrenceFrequency::DAILY =>
                $next->addDays(
                    $interval
                ),

            ChargeRecurrenceFrequency::WEEKLY =>
                $next->addWeeks(
                    $interval
                ),

            ChargeRecurrenceFrequency::MONTHLY =>
                $next->addMonthsNoOverflow(
                    $interval
                ),
        };
    }

    private function resolveReceivable(
        mixed $receivableId
    ): Receivable {
        if (
            $receivableId === null
            ||
            $receivableId === ''
        ) {
            throw new RuntimeException(
                'Charge recurrence receivable is required.'
            );
        }

        $receivable =
            Receivable::query()->find(
                (int) $receivableId
            );

        if ($receivable === null) {
            throw new RuntimeException(
                'Charge recurrence receivable is invalid.'
            );
        }

        return $receivable;
    }

    private function resolveRecurrence(
        ChargeRecurrence $recurrence
    ): ChargeRecurrence {
        $resolved =
            ChargeRecurrence::query()->find(
                $recurrence->id
            );

        if ($resolved === null) {
            throw new RuntimeException(
                'Charge recurrence does not belong to current tenant.'
            );
        }

        return $resolved;
    }
}