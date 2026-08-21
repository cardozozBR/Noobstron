<?php

namespace App\Support;

use App\Enums\ConditionOperator;
use InvalidArgumentException;

final readonly class AutomationCondition
{
    public function __construct(
        public string $field,
        public ConditionOperator $operator,
        public mixed $value = null,
    ) {
        $field = trim($this->field);

        if ($field === '') {
            throw new InvalidArgumentException(
                'Condition field is required.'
            );
        }

        if (
            $this->operator === ConditionOperator::IN
            && ! is_array($this->value)
        ) {
            throw new InvalidArgumentException(
                'Condition IN value must be an array.'
            );
        }
    }

    public static function make(
        string $field,
        ConditionOperator|string $operator,
        mixed $value = null,
    ): self {
        $operator = $operator instanceof ConditionOperator
            ? $operator
            : ConditionOperator::tryFrom(
                trim($operator)
            );

        if ($operator === null) {
            throw new InvalidArgumentException(
                'Invalid condition operator.'
            );
        }

        return new self(
            field: trim($field),
            operator: $operator,
            value: $value,
        );
    }
}