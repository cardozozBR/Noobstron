<?php

namespace App\Services;

use App\Enums\TriggerType;
use App\Models\Customer;
use App\Support\TriggerOccurrence;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CustomerInactiveTriggerService
{
    public function __construct(
        private TriggerDispatcher $triggers,
        private TriggerOccurrenceLedger $ledger
    ) {
    }

    public function dispatchForDate(
        int $inactiveDays,
        ?Carbon $today = null
    ): int {
        if ($inactiveDays < 1) {
            throw new InvalidArgumentException(
                'Inactive days must be at least 1.'
            );
        }

        $today ??= now();

        $today = $today
            ->copy()
            ->startOfDay();

        $cutoff = $today
            ->copy()
            ->subDays(
                $inactiveDays
            );

        $customers = Customer::query()
            ->withMax(
                'history',
                'created_at'
            )
            ->orderBy('id')
            ->get();

        $count = 0;

        foreach ($customers as $customer) {
            $lastActivityAt =
                $this->lastActivityAt(
                    $customer
                );

            if (
                $lastActivityAt->greaterThan(
                    $cutoff
                )
            ) {
                continue;
            }

            /*
             * The boundary identifies the inactivity period
             * that was reached from this last observed
             * customer-record activity.
             *
             * If new activity occurs later, lastActivityAt
             * changes and a future inactive period becomes
             * a legitimately different occurrence.
             */
            $boundary =
                $lastActivityAt
                    ->copy()
                    ->addDays(
                        $inactiveDays
                    )
                    ->toDateString();

            $occurrence =
                TriggerOccurrence::forTenant(
                    type:
                        TriggerType::CUSTOMER_INACTIVE,

                    tenant:
                        $customer->tenant,

                    subjectType:
                        Customer::class,

                    subjectId:
                        (int) $customer->id,

                    payload: [
                        'customer_id' =>
                            (int) $customer->id,

                        'inactive_days' =>
                            $inactiveDays,

                        'last_activity_at' =>
                            $lastActivityAt
                                ->toIso8601String(),

                        'inactive_since' =>
                            $boundary,

                        'evaluated_at' =>
                            $today
                                ->toIso8601String(),
                    ],

                    occurredAt:
                        $today
                );

            if (
                ! $this->ledger->claim(
                    $occurrence,
                    $boundary
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

    private function lastActivityAt(
        Customer $customer
    ): Carbon {
        $historyAt =
            $customer->history_max_created_at;

        if ($historyAt !== null) {
            return Carbon::parse(
                $historyAt
            );
        }

        /*
         * Legacy/imported customers may exist without a
         * customer_history entry. Their creation timestamp
         * is the safest observable fallback.
         */
        return Carbon::parse(
            $customer->created_at
        );
    }
}