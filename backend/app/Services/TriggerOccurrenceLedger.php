<?php

namespace App\Services;

use App\Models\TriggerOccurrenceRecord;
use App\Support\TriggerOccurrence;
use Illuminate\Database\QueryException;

class TriggerOccurrenceLedger
{
    public function claim(
        TriggerOccurrence $occurrence,
        string $boundary
    ): bool {
        $boundary = trim($boundary);

        if ($boundary === '') {
            throw new \InvalidArgumentException(
                'Trigger occurrence boundary is required.'
            );
        }

        if (
            $occurrence->subjectType === null
            || $occurrence->subjectId === null
        ) {
            throw new \InvalidArgumentException(
                'Persistent trigger occurrence requires a subject.'
            );
        }

        try {
            TriggerOccurrenceRecord::query()->create([
                'tenant_id' =>
                    $occurrence->tenantId,

                'trigger_name' =>
                    $occurrence->name(),

                'subject_type' =>
                    $occurrence->subjectType,

                'subject_id' =>
                    (string) $occurrence->subjectId,

                'boundary' =>
                    $boundary,

                'occurred_at' =>
                    $occurrence->occurredAt
                        ?? now(),
            ]);

            return true;
        } catch (QueryException $exception) {
            if (
                $this->isUniqueViolation(
                    $exception
                )
            ) {
                return false;
            }

            throw $exception;
        }
    }

    private function isUniqueViolation(
        QueryException $exception
    ): bool {
        $sqlState =
            $exception->errorInfo[0]
            ?? null;

        return in_array(
            $sqlState,
            [
                '23000',
                '23505',
            ],
            true
        );
    }
}