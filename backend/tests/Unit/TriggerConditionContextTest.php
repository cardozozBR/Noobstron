<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Enums\LeadStatus;
use App\Enums\ProposalStatus;
use App\Enums\TriggerType;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use App\Support\TriggerConditionContext;
use App\Support\TriggerOccurrence;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TriggerConditionContextTest extends TestCase
{
    public function test_context_exposes_trigger_metadata_and_payload(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                42,

            subjectType:
                'lead',

            subjectId:
                99,

            payload: [
                'name' =>
                    'Acme',

                'status' =>
                    LeadStatus::QUALIFIED,
            ],

            occurredAt:
                new DateTimeImmutable(
                    '2026-08-17T12:00:00+00:00'
                ),

            customName:
                'lead.scored',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertSame(
            'lead.scored',
            $context['trigger']['name']
        );

        $this->assertSame(
            'custom',
            $context['trigger']['type']
        );

        $this->assertSame(
            42,
            $context['tenant_id']
        );

        $this->assertSame(
            'lead',
            $context['subject']['type']
        );

        $this->assertSame(
            99,
            $context['subject']['id']
        );

        $this->assertSame(
            'Acme',
            $context['payload']['name']
        );

        $this->assertSame(
            'qualified',
            $context['payload']['status']
        );

        $this->assertSame(
            '2026-08-17T12:00:00+00:00',
            $context['occurred_at']
        );
    }

    public function test_nested_payload_values_are_normalized(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                1,

            payload: [
                'proposal' => [
                    'status' =>
                        ProposalStatus::SENT,

                    'allowed_statuses' => [
                        ProposalStatus::DRAFT,
                        ProposalStatus::SENT,
                    ],
                ],
            ],

            customName:
                'proposal.checked',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertSame(
            'sent',
            $context[
                'payload'
            ][
                'proposal'
            ][
                'status'
            ]
        );

        $this->assertSame(
            [
                'draft',
                'sent',
            ],
            $context[
                'payload'
            ][
                'proposal'
            ][
                'allowed_statuses'
            ]
        );
    }

    public function test_field_condition_matches_occurrence_payload(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                1,

            payload: [
                'customer' => [
                    'name' =>
                        'Acme Corporation',
                ],
            ],

            customName:
                'customer.enriched',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $condition =
            AutomationCondition::make(
                'payload.customer.name',
                ConditionOperator::CONTAINS,
                'Acme'
            );

        $this->assertTrue(
            (new ConditionEvaluator())
                ->matches(
                    $context,
                    $condition
                )
        );
    }

    public function test_status_condition_matches_normalized_payload(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                1,

            payload: [
                'status' =>
                    LeadStatus::QUALIFIED,
            ],

            customName:
                'lead.checked',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertTrue(
            (new ConditionEvaluator())
                ->matches(
                    $context,
                    AutomationCondition::make(
                        'payload.status',
                        ConditionOperator::EQUALS,
                        LeadStatus::QUALIFIED
                    )
                )
        );
    }

    public function test_trigger_name_can_be_used_as_field_condition(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::LEAD_CREATED,

            tenantId:
                7,

            payload: [
                'lead_id' => 15,
            ],
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertTrue(
            (new ConditionEvaluator())
                ->matches(
                    $context,
                    AutomationCondition::make(
                        'trigger.name',
                        ConditionOperator::EQUALS,
                        'lead.created'
                    )
                )
        );
    }

    public function test_missing_payload_field_can_use_null_operator(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::LEAD_CREATED,

            tenantId:
                7,

            payload: [],
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertTrue(
            (new ConditionEvaluator())
                ->matches(
                    $context,
                    AutomationCondition::make(
                        'payload.responsible_user_id',
                        ConditionOperator::IS_NULL
                    )
                )
        );
    }
}