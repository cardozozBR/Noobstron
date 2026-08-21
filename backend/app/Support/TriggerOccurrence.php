<?php

namespace App\Support;

use App\Enums\TriggerType;
use App\Models\Tenant;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class TriggerOccurrence
{
    public function __construct(
        public TriggerType $type,
        public int $tenantId,
        public ?string $subjectType = null,
        public int|string|null $subjectId = null,
        public array $payload = [],
        public ?DateTimeImmutable $occurredAt = null,
        public ?string $customName = null,
    ) {
        if ($this->tenantId <= 0) {
            throw new InvalidArgumentException(
                'Trigger tenant ID must be positive.'
            );
        }

        if (
            $this->type === TriggerType::CUSTOM
            && (
                $this->customName === null
                || trim($this->customName) === ''
            )
        ) {
            throw new InvalidArgumentException(
                'Custom trigger name is required.'
            );
        }

        if (
            $this->type !== TriggerType::CUSTOM
            && $this->customName !== null
        ) {
            throw new InvalidArgumentException(
                'Custom trigger name is only valid for custom triggers.'
            );
        }
    }

    public static function forTenant(
        TriggerType $type,
        Tenant $tenant,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $payload = [],
        ?DateTimeInterface $occurredAt = null,
        ?string $customName = null,
    ): self {
        return new self(
            type: $type,
            tenantId: (int) $tenant->getKey(),
            subjectType: $subjectType,
            subjectId: $subjectId,
            payload: $payload,
            occurredAt: $occurredAt !== null
                ? DateTimeImmutable::createFromInterface(
                    $occurredAt
                )
                : new DateTimeImmutable(),
            customName: $customName !== null
                ? trim($customName)
                : null,
        );
    }

    public function name(): string
    {
        if ($this->type !== TriggerType::CUSTOM) {
            return $this->type->value;
        }

        return (string) $this->customName;
    }
}
