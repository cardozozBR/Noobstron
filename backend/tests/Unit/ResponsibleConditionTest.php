<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Enums\TriggerType;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use App\Support\TriggerConditionContext;
use App\Support\TriggerOccurrence;
use PHPUnit\Framework\TestCase;

class ResponsibleConditionTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator =
            new ConditionEvaluator();
    }

    public function test_specific_responsible_can_match(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 15,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::EQUALS,
                    15
                )
            )
        );
    }

    public function test_numeric_string_responsible_id_can_match_integer(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 15,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::EQUALS,
                    '15'
                )
            )
        );
    }

    public function test_different_responsible_does_not_match(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 15,
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::EQUALS,
                    20
                )
            )
        );
    }

    public function test_not_equals_can_exclude_responsible(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 15,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::NOT_EQUALS,
                    20
                )
            )
        );
    }

    public function test_responsible_can_match_allowed_group(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 15,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IN,
                    [
                        10,
                        15,
                        20,
                    ]
                )
            )
        );
    }

    public function test_unassigned_is_represented_by_null(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => null,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IS_NULL
                )
            )
        );

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IS_NOT_NULL
                )
            )
        );
    }

    public function test_assigned_responsible_is_not_null(): void
    {
        $context = [
            'payload' => [
                'responsible_user_id' => 25,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IS_NOT_NULL
                )
            )
        );
    }

    public function test_missing_responsible_field_is_treated_as_null(): void
    {
        $context = [
            'payload' => [],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IS_NULL
                )
            )
        );
    }

    public function test_responsible_condition_can_be_combined_with_tenant(): void
    {
        $context = [
            'tenant_id' => 10,

            'payload' => [
                'responsible_user_id' => 50,
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'tenant_id',
                ConditionOperator::EQUALS,
                10
            ),

            AutomationCondition::make(
                'payload.responsible_user_id',
                ConditionOperator::EQUALS,
                50
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_responsible_condition_does_not_hide_tenant_mismatch(): void
    {
        $context = [
            'tenant_id' => 20,

            'payload' => [
                'responsible_user_id' => 50,
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'tenant_id',
                ConditionOperator::EQUALS,
                10
            ),

            AutomationCondition::make(
                'payload.responsible_user_id',
                ConditionOperator::EQUALS,
                50
            ),
        ];

        $this->assertFalse(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_trigger_context_preserves_responsible_id(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                11,

            payload: [
                'responsible_user_id' => 44,
            ],

            customName:
                'customer.assigned',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertSame(
            44,
            $context[
                'payload'
            ][
                'responsible_user_id'
            ]
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::EQUALS,
                    44
                )
            )
        );
    }

    public function test_unassigned_trigger_context_preserves_null(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                11,

            payload: [
                'responsible_user_id' => null,
            ],

            customName:
                'customer.unassigned',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertNull(
            $context[
                'payload'
            ][
                'responsible_user_id'
            ]
        );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.responsible_user_id',
                    ConditionOperator::IS_NULL
                )
            )
        );
    }
}