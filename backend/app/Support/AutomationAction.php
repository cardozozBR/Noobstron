<?php

namespace App\Support;

use App\Enums\AutomationActionType;
use InvalidArgumentException;

final readonly class AutomationAction
{
    public function __construct(
        public int $tenantId,
        public AutomationActionType $type,
        public array $parameters = [],
    ) {
        if ($this->tenantId <= 0) {
            throw new InvalidArgumentException(
                'Automation action tenant is required.'
            );
        }
    }

    public static function make(
        int $tenantId,
        AutomationActionType|string $type,
        array $parameters = [],
    ): self {
        $type = $type instanceof AutomationActionType
            ? $type
            : AutomationActionType::tryFrom(
                trim($type)
            );

        if ($type === null) {
            throw new InvalidArgumentException(
                'Invalid automation action type.'
            );
        }

        return new self(
            tenantId: $tenantId,
            type: $type,
            parameters: $parameters,
        );
    }
}