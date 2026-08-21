<?php

namespace App\Services;

use App\Enums\TriggerType;
use App\Models\Tenant;
use App\Support\TriggerOccurrence;
use DateTimeInterface;
use InvalidArgumentException;

class CustomTriggerService
{
    public function __construct(
        private TriggerDispatcher $triggers
    ) {
    }

    public function dispatch(
        Tenant $tenant,
        string $name,
        array $payload = [],
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?DateTimeInterface $occurredAt = null,
    ): TriggerOccurrence {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException(
                'Custom trigger name is required.'
            );
        }

        if (
            ($subjectType === null) !==
            ($subjectId === null)
        ) {
            throw new InvalidArgumentException(
                'Custom trigger subject type and ID must be provided together.'
            );
        }

        $occurrence = TriggerOccurrence::forTenant(
            type: TriggerType::CUSTOM,
            tenant: $tenant,
            subjectType: $subjectType,
            subjectId: $subjectId,
            payload: $payload,
            occurredAt: $occurredAt,
            customName: $name,
        );

        $this->triggers->dispatch(
            $occurrence
        );

        return $occurrence;
    }
}