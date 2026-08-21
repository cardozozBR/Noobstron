<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Enums\ReceivableStatus;
use App\Models\Charge;
use App\Models\Receivable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChargeService
{
    public function create(
        array $data
    ): Charge {
        return DB::transaction(
            function () use ($data): Charge {
                $receivable = $this->resolveReceivable(
                    $data['receivable_id'] ?? null
                );

                if (
                    $receivable->status !==
                    ReceivableStatus::PENDING
                ) {
                    throw new RuntimeException(
                        'Only pending receivables can be charged.'
                    );
                }

                $attempt = $this->nextAttempt(
                    $receivable
                );

                $charge = Charge::query()->create([
                    'receivable_id' => $receivable->id,
                    'status' => ChargeStatus::PENDING,
                    'attempt' => $attempt,
                    'scheduled_at' =>
                        $data['scheduled_at'] ?? null,
                    'channel' =>
                        $this->normalizeNullableText(
                            $data['channel'] ?? null
                        ),
                    'recipient' =>
                        $this->normalizeNullableText(
                            $data['recipient'] ?? null
                        ),
                    'external_reference' =>
                        $this->normalizeNullableText(
                            $data['external_reference']
                                ?? null
                        ),
                ]);

                app(AuditService::class)->log(
                    'charge.created',
                    'Cobrança criada para a conta a receber '
                        . $receivable->id
                        . '. Tentativa: '
                        . $charge->attempt
                        . '.'
                );

                return $charge;
            }
        );
    }

    public function markSent(
        Charge $charge,
        ?string $externalReference = null
    ): Charge {
        $charge = $this->resolveCharge(
            $charge
        );

        if (
            $charge->status !==
            ChargeStatus::PENDING
        ) {
            throw new RuntimeException(
                'Only pending charges can be sent.'
            );
        }

        return DB::transaction(
            function () use (
                $charge,
                $externalReference
            ): Charge {
                $charge->status =
                    ChargeStatus::SENT;

                $charge->sent_at = now();

                if ($externalReference !== null) {
                    $charge->external_reference =
                        $this->normalizeNullableText(
                            $externalReference
                        );
                }

                $charge->failure_reason = null;
                $charge->failed_at = null;

                $charge->save();

                app(AuditService::class)->log(
                    'charge.sent',
                    'Cobrança enviada. ID: '
                        . $charge->id
                        . '. Tentativa: '
                        . $charge->attempt
                        . '.'
                );

                return $charge->refresh();
            }
        );
    }

    public function markFailed(
        Charge $charge,
        string $reason
    ): Charge {
        $charge = $this->resolveCharge(
            $charge
        );

        if (
            ! in_array(
                $charge->status,
                [
                    ChargeStatus::PENDING,
                    ChargeStatus::SENT,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Charge cannot be marked as failed.'
            );
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException(
                'Charge failure reason is required.'
            );
        }

        return DB::transaction(
            function () use (
                $charge,
                $reason
            ): Charge {
                $charge->status =
                    ChargeStatus::FAILED;

                $charge->failed_at = now();
                $charge->failure_reason = $reason;

                $charge->save();

                app(AuditService::class)->log(
                    'charge.failed',
                    'Cobrança falhou. ID: '
                        . $charge->id
                        . '. Motivo: '
                        . $reason
                        . '.'
                );

                return $charge->refresh();
            }
        );
    }

    public function cancel(
        Charge $charge
    ): Charge {
        $charge = $this->resolveCharge(
            $charge
        );

        if (
            ! in_array(
                $charge->status,
                [
                    ChargeStatus::PENDING,
                    ChargeStatus::SENT,
                    ChargeStatus::FAILED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Charge cannot be cancelled.'
            );
        }

        return DB::transaction(
            function () use (
                $charge
            ): Charge {
                $charge->status =
                    ChargeStatus::CANCELLED;

                $charge->cancelled_at = now();

                $charge->save();

                app(AuditService::class)->log(
                    'charge.cancelled',
                    'Cobrança cancelada. ID: '
                        . $charge->id
                        . '. Tentativa: '
                        . $charge->attempt
                        . '.'
                );

                return $charge->refresh();
            }
        );
    }

    public function syncReceivablePaid(
        Receivable $receivable
    ): void {
        $receivable = $this->resolveReceivable(
            $receivable->id
        );

        if (
            $receivable->status !==
            ReceivableStatus::PAID
        ) {
            return;
        }

        DB::transaction(
            function () use ($receivable): void {
                $charges = Charge::query()
                    ->where(
                        'receivable_id',
                        $receivable->id
                    )
                    ->whereIn(
                        'status',
                        [
                            ChargeStatus::PENDING->value,
                            ChargeStatus::SENT->value,
                            ChargeStatus::FAILED->value,
                        ]
                    )
                    ->get();

                foreach ($charges as $charge) {
                    $charge->status =
                        ChargeStatus::PAID;

                    $charge->paid_at =
                        $receivable->paid_at
                        ?? now();

                    $charge->save();
                }
            }
        );
    }

    private function nextAttempt(
        Receivable $receivable
    ): int {
        $lastAttempt = Charge::query()
            ->where(
                'receivable_id',
                $receivable->id
            )
            ->max('attempt');

        return ((int) $lastAttempt) + 1;
    }

    private function resolveReceivable(
        mixed $receivableId
    ): Receivable {
        if (
            $receivableId === null ||
            $receivableId === ''
        ) {
            throw new RuntimeException(
                'Charge receivable is required.'
            );
        }

        $receivable = Receivable::query()->find(
            (int) $receivableId
        );

        if ($receivable === null) {
            throw new RuntimeException(
                'Charge receivable is invalid.'
            );
        }

        return $receivable;
    }

    private function resolveCharge(
        Charge $charge
    ): Charge {
        $resolved = Charge::query()->find(
            $charge->id
        );

        if ($resolved === null) {
            throw new RuntimeException(
                'Charge does not belong to current tenant.'
            );
        }

        return $resolved;
    }

    private function normalizeNullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}