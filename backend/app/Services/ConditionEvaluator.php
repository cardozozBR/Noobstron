<?php

namespace App\Services;

use App\Enums\ConditionOperator;
use App\Support\AutomationCondition;
use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Throwable;
use UnitEnum;

class ConditionEvaluator
{
    public function matches(
        array|object $context,
        AutomationCondition $condition
    ): bool {
        $actual = data_get(
            $context,
            $condition->field
        );

        return match ($condition->operator) {
            ConditionOperator::EQUALS =>
                $this->equals(
                    $actual,
                    $condition->value
                ),

            ConditionOperator::NOT_EQUALS =>
                ! $this->equals(
                    $actual,
                    $condition->value
                ),

            ConditionOperator::GREATER_THAN =>
                $this->compareNumeric(
                    $actual,
                    $condition->value,
                    fn (
                        int|float $left,
                        int|float $right
                    ): bool =>
                        $left > $right
                ),

            ConditionOperator::GREATER_THAN_OR_EQUAL =>
                $this->compareNumeric(
                    $actual,
                    $condition->value,
                    fn (
                        int|float $left,
                        int|float $right
                    ): bool =>
                        $left >= $right
                ),

            ConditionOperator::LESS_THAN =>
                $this->compareNumeric(
                    $actual,
                    $condition->value,
                    fn (
                        int|float $left,
                        int|float $right
                    ): bool =>
                        $left < $right
                ),

            ConditionOperator::LESS_THAN_OR_EQUAL =>
                $this->compareNumeric(
                    $actual,
                    $condition->value,
                    fn (
                        int|float $left,
                        int|float $right
                    ): bool =>
                        $left <= $right
                ),

            ConditionOperator::CONTAINS =>
                $this->contains(
                    $actual,
                    $condition->value
                ),

            ConditionOperator::IN =>
                $this->in(
                    $actual,
                    $condition->value
                ),

            ConditionOperator::IS_NULL =>
                $actual === null,

            ConditionOperator::IS_NOT_NULL =>
                $actual !== null,

            ConditionOperator::BEFORE =>
                $this->compareTemporal(
                    $actual,
                    $condition->value,
                    fn (
                        DateTimeImmutable $left,
                        DateTimeImmutable $right
                    ): bool =>
                        $left < $right
                ),

            ConditionOperator::BEFORE_OR_EQUAL =>
                $this->compareTemporal(
                    $actual,
                    $condition->value,
                    fn (
                        DateTimeImmutable $left,
                        DateTimeImmutable $right
                    ): bool =>
                        $left <= $right
                ),

            ConditionOperator::AFTER =>
                $this->compareTemporal(
                    $actual,
                    $condition->value,
                    fn (
                        DateTimeImmutable $left,
                        DateTimeImmutable $right
                    ): bool =>
                        $left > $right
                ),

            ConditionOperator::AFTER_OR_EQUAL =>
                $this->compareTemporal(
                    $actual,
                    $condition->value,
                    fn (
                        DateTimeImmutable $left,
                        DateTimeImmutable $right
                    ): bool =>
                        $left >= $right
                ),
        };
    }

    /**
     * @param list<AutomationCondition> $conditions
     */
    public function matchesAll(
        array|object $context,
        array $conditions
    ): bool {
        foreach ($conditions as $condition) {
            if (! $condition instanceof AutomationCondition) {
                throw new InvalidArgumentException(
                    'Conditions must contain AutomationCondition instances.'
                );
            }

            if (
                ! $this->matches(
                    $context,
                    $condition
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<AutomationCondition> $conditions
     */
    public function matchesAny(
        array|object $context,
        array $conditions
    ): bool {
        foreach ($conditions as $condition) {
            if (! $condition instanceof AutomationCondition) {
                throw new InvalidArgumentException(
                    'Conditions must contain AutomationCondition instances.'
                );
            }

            if (
                $this->matches(
                    $context,
                    $condition
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function equals(
        mixed $actual,
        mixed $expected
    ): bool {
        $actual =
            $this->normalizeComparable(
                $actual
            );

        $expected =
            $this->normalizeComparable(
                $expected
            );

        if (
            is_int($actual)
            || is_float($actual)
        ) {
            if (! is_numeric($expected)) {
                return false;
            }

            return (float) $actual ===
                (float) $expected;
        }

        return $actual === $expected;
    }

    private function compareNumeric(
        mixed $actual,
        mixed $expected,
        callable $comparison
    ): bool {
        if (
            ! is_numeric($actual)
            || ! is_numeric($expected)
        ) {
            return false;
        }

        return $comparison(
            $this->number($actual),
            $this->number($expected)
        );
    }

    private function contains(
        mixed $actual,
        mixed $expected
    ): bool {
        $expected =
            $this->normalizeComparable(
                $expected
            );

        if (is_string($actual)) {
            if (
                ! is_string($expected)
                && ! is_numeric($expected)
            ) {
                return false;
            }

            return str_contains(
                $actual,
                (string) $expected
            );
        }

        if (is_array($actual)) {
            foreach ($actual as $value) {
                if (
                    $this->equals(
                        $value,
                        $expected
                    )
                ) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private function in(
        mixed $actual,
        mixed $expected
    ): bool {
        if (! is_array($expected)) {
            return false;
        }

        foreach ($expected as $candidate) {
            if (
                $this->equals(
                    $actual,
                    $candidate
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function compareTemporal(
        mixed $actual,
        mixed $expected,
        callable $comparison
    ): bool {
        $left =
            $this->dateTime(
                $actual
            );

        $right =
            $this->dateTime(
                $expected
            );

        if (
            $left === null
            || $right === null
        ) {
            return false;
        }

        return $comparison(
            $left,
            $right
        );
    }

    private function dateTime(
        mixed $value
    ): ?DateTimeImmutable {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface(
                $value
            );
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable(
                $value
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeComparable(
        mixed $value
    ): mixed {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }

    private function number(
        mixed $value
    ): int|float {
        $string = (string) $value;

        if (
            ! str_contains(
                $string,
                '.'
            )
            && filter_var(
                $string,
                FILTER_VALIDATE_INT
            ) !== false
        ) {
            return (int) $string;
        }

        return (float) $string;
    }
}