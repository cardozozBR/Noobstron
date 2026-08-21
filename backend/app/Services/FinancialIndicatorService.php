<?php

namespace App\Services;

use App\Enums\ReceivableStatus;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialIndicatorService
{
    public function receivedMinor(
        ?Carbon $from = null,
        ?Carbon $until = null
    ): int {
        $query = Receivable::query()
            ->where(
                'status',
                ReceivableStatus::PAID->value
            )
            ->whereNotNull(
                'paid_at'
            );

        if ($from !== null) {
            $query->where(
                'paid_at',
                '>=',
                $from
            );
        }

        if ($until !== null) {
            $query->where(
                'paid_at',
                '<=',
                $until
            );
        }

        return (int) $query->sum(
            'amount_minor'
        );
    }

    public function outstandingMinor(): int
    {
        return (int) Receivable::query()
            ->where(
                'status',
                ReceivableStatus::PENDING->value
            )
            ->sum(
                'amount_minor'
            );
    }

    public function overdueMinor(
        ?Carbon $today = null
    ): int {
        $today ??= now();

        return (int) Receivable::query()
            ->where(
                'status',
                ReceivableStatus::PENDING->value
            )
            ->whereDate(
                'due_date',
                '<',
                $today->toDateString()
            )
            ->sum(
                'amount_minor'
            );
    }

    public function revenueByPeriod(
        Carbon $from,
        Carbon $until
    ): Collection {
        return Receivable::query()
            ->where(
                'status',
                ReceivableStatus::PAID->value
            )
            ->whereNotNull(
                'paid_at'
            )
            ->whereBetween(
                'paid_at',
                [
                    $from,
                    $until,
                ]
            )
            ->selectRaw(
                'DATE(paid_at) as period_date, SUM(amount_minor) as amount_minor'
            )
            ->groupByRaw(
                'DATE(paid_at)'
            )
            ->orderBy(
                'period_date'
            )
            ->get()
            ->map(
                fn ($row) => [
                    'date' =>
                        (string) $row->period_date,
                    'amount_minor' =>
                        (int) $row->amount_minor,
                ]
            )
            ->values();
    }

    public function revenueByCustomer(
        ?Carbon $from = null,
        ?Carbon $until = null
    ): Collection {
        $query = Receivable::query()
            ->with('customer')
            ->where(
                'status',
                ReceivableStatus::PAID->value
            )
            ->whereNotNull(
                'paid_at'
            );

        if ($from !== null) {
            $query->where(
                'paid_at',
                '>=',
                $from
            );
        }

        if ($until !== null) {
            $query->where(
                'paid_at',
                '<=',
                $until
            );
        }

        return $query
            ->get()
            ->groupBy(
                'customer_id'
            )
            ->map(
                function (
                    Collection $receivables
                ): array {
                    $first =
                        $receivables->first();

                    return [
                        'customer_id' =>
                            $first->customer_id,
                        'customer_name' =>
                            $first->customer?->name,
                        'amount_minor' =>
                            (int) $receivables
                                ->sum(
                                    'amount_minor'
                                ),
                    ];
                }
            )
            ->sortByDesc(
                'amount_minor'
            )
            ->values();
    }

    public function summary(
        ?Carbon $today = null
    ): array {
        $today ??= now();

        return [
            'received_minor' =>
                $this->receivedMinor(),

            'outstanding_minor' =>
                $this->outstandingMinor(),

            'overdue_minor' =>
                $this->overdueMinor(
                    $today
                ),
        ];
    }
}