<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Enums\TriggerType;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use App\Support\TriggerConditionContext;
use App\Support\TriggerOccurrence;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class TimeConditionTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator =
            new ConditionEvaluator();
    }

    public function test_before_operator_matches_iso_timestamp(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T10:00:00+00:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::BEFORE,
                    '2026-08-17T11:00:00+00:00'
                )
            )
        );
    }

    public function test_after_operator_matches_iso_timestamp(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T12:00:00+00:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER,
                    '2026-08-17T11:00:00+00:00'
                )
            )
        );
    }

    public function test_before_or_equal_accepts_same_instant(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T12:00:00+00:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::BEFORE_OR_EQUAL,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );
    }

    public function test_after_or_equal_accepts_same_instant(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T12:00:00+00:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER_OR_EQUAL,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );
    }

    public function test_timezone_offsets_represent_same_instant(): void
    {
        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T09:00:00-03:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::BEFORE_OR_EQUAL,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T09:00:00-03:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER_OR_EQUAL,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );
    }

    public function test_datetime_interface_is_supported(): void
    {
        $actual = new DateTimeImmutable(
            '2026-08-17 10:00:00',
            new DateTimeZone(
                'America/Fortaleza'
            )
        );

        $expected = new DateTimeImmutable(
            '2026-08-17 13:00:00',
            new DateTimeZone(
                'UTC'
            )
        );

        $this->assertTrue(
            $this->evaluator->matches(
                [
                    'occurred_at' => $actual,
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER_OR_EQUAL,
                    $expected
                )
            )
        );
    }

    public function test_invalid_actual_date_returns_false(): void
    {
        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        'not-a-date',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );
    }

    public function test_invalid_expected_date_returns_false(): void
    {
        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'occurred_at' =>
                        '2026-08-17T12:00:00+00:00',
                ],
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER,
                    'invalid-date'
                )
            )
        );
    }

    public function test_null_temporal_value_does_not_match(): void
    {
        $this->assertFalse(
            $this->evaluator->matches(
                [
                    'paid_at' => null,
                ],
                AutomationCondition::make(
                    'paid_at',
                    ConditionOperator::BEFORE,
                    '2026-08-17T12:00:00+00:00'
                )
            )
        );
    }

    public function test_payload_due_date_can_be_compared(): void
    {
        $context = [
            'payload' => [
                'due_date' =>
                    '2026-08-10',
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.due_date',
                    ConditionOperator::BEFORE,
                    '2026-08-17'
                )
            )
        );
    }

    public function test_trigger_occurred_at_context_can_be_compared(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::LEAD_CREATED,

            tenantId:
                10,

            payload: [
                'lead_id' => 20,
            ],

            occurredAt:
                new DateTimeImmutable(
                    '2026-08-17T09:30:00-03:00'
                ),
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'occurred_at',
                    ConditionOperator::AFTER,
                    '2026-08-17T09:00:00-03:00'
                )
            )
        );
    }

    public function test_temporal_conditions_can_be_combined(): void
    {
        $context = [
            'payload' => [
                'created_at' =>
                    '2026-08-01T10:00:00+00:00',

                'updated_at' =>
                    '2026-08-10T10:00:00+00:00',
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.created_at',
                ConditionOperator::BEFORE,
                '2026-08-05T00:00:00+00:00'
            ),

            AutomationCondition::make(
                'payload.updated_at',
                ConditionOperator::AFTER,
                '2026-08-05T00:00:00+00:00'
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }
}