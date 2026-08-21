<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Enums\LeadStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator =
            new ConditionEvaluator();
    }

    public function test_operator_catalog_contains_expected_values(): void
    {
        $this->assertSame(
            [
                'equals',
                'not_equals',
                'greater_than',
                'greater_than_or_equal',
                'less_than',
                'less_than_or_equal',
                'contains',
                'in',
                'is_null',
                'is_not_null',
                'before',
                'before_or_equal',
                'after',
                'after_or_equal',
            ],
            array_map(
                fn (
                    ConditionOperator $operator
                ): string =>
                    $operator->value,
                ConditionOperator::cases()
            )
        );
    }

    public function test_condition_can_be_created_from_string_operator(): void
    {
        $condition = AutomationCondition::make(
            'status',
            'equals',
            'open'
        );

        $this->assertSame(
            'status',
            $condition->field
        );

        $this->assertSame(
            ConditionOperator::EQUALS,
            $condition->operator
        );

        $this->assertSame(
            'open',
            $condition->value
        );
    }

    public function test_condition_requires_field(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        AutomationCondition::make(
            '   ',
            ConditionOperator::EQUALS,
            'open'
        );
    }

    public function test_invalid_operator_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        AutomationCondition::make(
            'status',
            'invalid_operator',
            'open'
        );
    }

    public function test_in_requires_array_value(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        AutomationCondition::make(
            'status',
            ConditionOperator::IN,
            'open'
        );
    }

    public function test_equals_matches_scalar_value(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' => 'open',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::EQUALS,
                    'open'
                )
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'status' => 'closed',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::EQUALS,
                    'open'
                )
            )
        );
    }

    public function test_not_equals_is_supported(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' => 'closed',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::NOT_EQUALS,
                    'open'
                )
            )
        );
    }

    public function test_nested_field_uses_dot_notation(): void
    {
        $context = [
            'customer' => [
                'type' => 'company',
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'customer.type',
                    ConditionOperator::EQUALS,
                    'company'
                )
            )
        );
    }

    public function test_numeric_comparisons_support_minor_values(): void
    {
        $context = [
            'value_minor' => 15000,
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'value_minor',
                    ConditionOperator::GREATER_THAN,
                    10000
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'value_minor',
                    ConditionOperator::GREATER_THAN_OR_EQUAL,
                    '15000'
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'value_minor',
                    ConditionOperator::LESS_THAN,
                    20000
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'value_minor',
                    ConditionOperator::LESS_THAN_OR_EQUAL,
                    15000
                )
            )
        );
    }

    public function test_invalid_numeric_comparison_returns_false(): void
    {
        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'value_minor' =>
                        'not-a-number',
                ],
                AutomationCondition::make(
                    'value_minor',
                    ConditionOperator::GREATER_THAN,
                    100
                )
            )
        );
    }

    public function test_string_contains_is_supported(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'name' =>
                        'Acme Corporation',
                ],
                AutomationCondition::make(
                    'name',
                    ConditionOperator::CONTAINS,
                    'Corporation'
                )
            )
        );
    }

    public function test_array_contains_is_supported(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'tags' => [
                        'vip',
                        'enterprise',
                    ],
                ],
                AutomationCondition::make(
                    'tags',
                    ConditionOperator::CONTAINS,
                    'vip'
                )
            )
        );
    }

    public function test_in_operator_is_supported(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' => 'pending',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::IN,
                    [
                        'open',
                        'pending',
                    ]
                )
            )
        );
    }

    public function test_null_operators_are_supported(): void
    {
        $context = [
            'responsible_user_id' =>
                null,
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'responsible_user_id',
                    ConditionOperator::IS_NULL
                )
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'responsible_user_id',
                    ConditionOperator::IS_NOT_NULL
                )
            )
        );
    }

    public function test_missing_field_is_null(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [],
                AutomationCondition::make(
                    'missing_field',
                    ConditionOperator::IS_NULL
                )
            )
        );
    }

    public function test_boolean_equality_is_strict(): void
    {
        $condition = AutomationCondition::make(
            'active',
            ConditionOperator::EQUALS,
            true
        );

        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'active' => true,
                ],
                $condition
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'active' => 1,
                ],
                $condition
            )
        );
    }

    public function test_matches_all_requires_every_condition(): void
    {
        $context = [
            'status' => 'open',
            'value_minor' => 25000,
        ];

        $conditions = [
            AutomationCondition::make(
                'status',
                ConditionOperator::EQUALS,
                'open'
            ),
            AutomationCondition::make(
                'value_minor',
                ConditionOperator::GREATER_THAN,
                10000
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_matches_all_returns_false_when_one_fails(): void
    {
        $context = [
            'status' => 'closed',
            'value_minor' => 25000,
        ];

        $conditions = [
            AutomationCondition::make(
                'status',
                ConditionOperator::EQUALS,
                'open'
            ),
            AutomationCondition::make(
                'value_minor',
                ConditionOperator::GREATER_THAN,
                10000
            ),
        ];

        $this->assertFalse(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_matches_any_requires_only_one_condition(): void
    {
        $context = [
            'status' => 'closed',
            'value_minor' => 25000,
        ];

        $conditions = [
            AutomationCondition::make(
                'status',
                ConditionOperator::EQUALS,
                'open'
            ),
            AutomationCondition::make(
                'value_minor',
                ConditionOperator::GREATER_THAN,
                10000
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAny(
                $context,
                $conditions
            )
        );
    }

    public function test_condition_groups_reject_invalid_items(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->evaluator->matchesAll(
            [],
            [
                'invalid',
            ]
        );
    }

    public function test_object_context_is_supported(): void
    {
        $context = (object) [
            'status' => 'pending',
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'status',
                    ConditionOperator::EQUALS,
                    'pending'
                )
            )
        );
    }
    public function test_backed_enum_status_matches_string_value(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' =>
                        LeadStatus::QUALIFIED,
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::EQUALS,
                    'qualified'
                )
            )
        );
    }

    public function test_string_status_matches_backed_enum_value(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' => 'sent',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::EQUALS,
                    ProposalStatus::SENT
                )
            )
        );
    }

    public function test_status_in_accepts_enum_candidates(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' => 'pending',
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::IN,
                    [
                        ReceivableStatus::PENDING,
                        ReceivableStatus::PAID,
                    ]
                )
            )
        );
    }

    public function test_status_not_equals_normalizes_enum(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'status' =>
                        LeadStatus::CONTACTED,
                ],
                AutomationCondition::make(
                    'status',
                    ConditionOperator::NOT_EQUALS,
                    LeadStatus::NEW
                )
            )
        );
    }

    public function test_array_contains_normalizes_enum_values(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'statuses' => [
                        ProposalStatus::DRAFT,
                        ProposalStatus::SENT,
                    ],
                ],
                AutomationCondition::make(
                    'statuses',
                    ConditionOperator::CONTAINS,
                    'sent'
                )
            )
        );
    }
}