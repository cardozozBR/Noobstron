<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use PHPUnit\Framework\TestCase;

class ValueConditionTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator =
            new ConditionEvaluator();
    }

    public function test_opportunity_value_minor_can_be_compared(): void
    {
        $context = [
            'payload' => [
                'value_minor' => 250000,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.value_minor',
                    ConditionOperator::GREATER_THAN,
                    100000
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.value_minor',
                    ConditionOperator::LESS_THAN_OR_EQUAL,
                    250000
                )
            )
        );
    }

    public function test_receivable_amount_minor_can_be_compared(): void
    {
        $context = [
            'payload' => [
                'amount_minor' => 9999,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::EQUALS,
                    9999
                )
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::GREATER_THAN,
                    10000
                )
            )
        );
    }

    public function test_numeric_string_expected_value_is_supported(): void
    {
        $context = [
            'payload' => [
                'amount_minor' => 15000,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::EQUALS,
                    '15000'
                )
            )
        );
    }

    public function test_probability_can_be_compared(): void
    {
        $context = [
            'payload' => [
                'probability' => 75,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.probability',
                    ConditionOperator::GREATER_THAN_OR_EQUAL,
                    50
                )
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.probability',
                    ConditionOperator::LESS_THAN,
                    50
                )
            )
        );
    }

    public function test_zero_value_is_valid(): void
    {
        $context = [
            'payload' => [
                'value_minor' => 0,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.value_minor',
                    ConditionOperator::EQUALS,
                    0
                )
            )
        );
    }

    public function test_negative_numeric_values_still_compare_consistently(): void
    {
        $context = [
            'payload' => [
                'adjustment_minor' => -500,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.adjustment_minor',
                    ConditionOperator::LESS_THAN,
                    0
                )
            )
        );
    }

    public function test_invalid_actual_numeric_value_does_not_match(): void
    {
        $context = [
            'payload' => [
                'amount_minor' => 'invalid',
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::GREATER_THAN,
                    0
                )
            )
        );
    }

    public function test_invalid_expected_numeric_value_does_not_match(): void
    {
        $context = [
            'payload' => [
                'amount_minor' => 1000,
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::GREATER_THAN,
                    'invalid'
                )
            )
        );
    }

    public function test_numeric_conditions_can_be_combined(): void
    {
        $context = [
            'payload' => [
                'value_minor' => 50000,
                'probability' => 80,
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.value_minor',
                ConditionOperator::GREATER_THAN_OR_EQUAL,
                50000
            ),
            AutomationCondition::make(
                'payload.probability',
                ConditionOperator::GREATER_THAN,
                70
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_value_condition_does_not_convert_major_currency_units(): void
    {
        $context = [
            'payload' => [
                'amount_minor' => 1050,
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::EQUALS,
                    10.50
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.amount_minor',
                    ConditionOperator::EQUALS,
                    1050
                )
            )
        );
    }
}