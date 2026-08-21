<?php

namespace App\Support;

use BackedEnum;
use DateTimeInterface;
use UnitEnum;

final class TriggerConditionContext
{
    public function make(
        TriggerOccurrence $occurrence
    ): array {
        return [
            'trigger' => [
                'name' =>
                    $occurrence->name(),

                'type' =>
                    $occurrence->type->value,
            ],

            'tenant_id' =>
                $occurrence->tenantId,

            'subject' => [
                'type' =>
                    $occurrence->subjectType,

                'id' =>
                    $occurrence->subjectId,
            ],

            'payload' =>
                $this->normalize(
                    $occurrence->payload
                ),

            'occurred_at' =>
                $occurrence->occurredAt
                    ?->format(
                        DATE_ATOM
                    ),
        ];
    }

    private function normalize(
        mixed $value
    ): mixed {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(
                DATE_ATOM
            );
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] =
                    $this->normalize(
                        $item
                    );
            }

            return $normalized;
        }

        return $value;
    }
}