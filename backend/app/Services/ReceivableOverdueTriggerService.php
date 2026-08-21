<?php

namespace App\Services;

use App\Enums\ReceivableStatus;
use App\Enums\TriggerType;
use App\Models\Receivable;
use App\Support\TriggerOccurrence;
use Illuminate\Support\Carbon;

class ReceivableOverdueTriggerService
{
    public function __construct(
        private TriggerDispatcher $triggers,
        private TriggerOccurrenceLedger $ledger
    ) {
    }

    public function dispatchForDate(
        ?Carbon $today = null
    ): int {
        $today ??= now();

        $today = $today
            ->copy()
            ->startOfDay();

        $receivables = Receivable::query()
            ->where(
                'status',
                ReceivableStatus::PENDING->value
            )
            ->whereDate(
                'due_date',
                '<',
                $today->toDateString()
            )
            ->orderBy(
                'due_date'
            )
            ->orderBy(
                'id'
            )
            ->get();

        $count = 0;

        foreach ($receivables as $receivable) {
            $dueDate =
                $receivable->due_date
                    ->toDateString();

            $occurrence =
                TriggerOccurrence::forTenant(
                    type:
                        TriggerType::RECEIVABLE_OVERDUE,

                    tenant:
                        $receivable->tenant,

                    subjectType:
                        Receivable::class,

                    subjectId:
                        (int) $receivable->id,

                    payload: [
                        'receivable_id' =>
                            (int) $receivable->id,

                        'customer_id' =>
                            (int) $receivable->customer_id,

                        'sale_id' =>
                            $receivable->sale_id !== null
                                ? (int) $receivable->sale_id
                                : null,

                        'due_date' =>
                            $dueDate,

                        'amount_minor' =>
                            (int) $receivable->amount_minor,

                        'currency' =>
                            $receivable->currency,

                        'status' =>
                            $receivable->status->value,

                       'overdue_days' =>
    (int) $receivable->due_date
        ->copy()
        ->startOfDay()
        ->diffInDays(
            $today
        ),
                    ],

                    occurredAt:
                        $today
                );

            if (
                ! $this->ledger->claim(
                    $occurrence,
                    $dueDate
                )
            ) {
                continue;
            }

            $this->triggers->dispatch(
                $occurrence
            );

            $count++;
        }

        return $count;
    }
}