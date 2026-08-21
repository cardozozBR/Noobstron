<?php

namespace Tests\Unit;

use App\Enums\ConditionOperator;
use App\Enums\CustomerType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\TriggerType;
use App\Services\ConditionEvaluator;
use App\Support\AutomationCondition;
use App\Support\TriggerConditionContext;
use App\Support\TriggerOccurrence;
use PHPUnit\Framework\TestCase;

class SegmentationConditionTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator =
            new ConditionEvaluator();
    }

    public function test_tag_can_define_segment(): void
    {
        $context = [
            'payload' => [
                'tags' => [
                    'vip',
                    'enterprise',
                ],
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.tags',
                    ConditionOperator::CONTAINS,
                    'vip'
                )
            )
        );
    }

    public function test_missing_tag_does_not_match_segment(): void
    {
        $context = [
            'payload' => [
                'tags' => [
                    'standard',
                ],
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.tags',
                    ConditionOperator::CONTAINS,
                    'vip'
                )
            )
        );
    }

    public function test_multiple_required_tags_use_matches_all(): void
    {
        $context = [
            'payload' => [
                'tags' => [
                    'vip',
                    'enterprise',
                    'renewal',
                ],
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'vip'
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'enterprise'
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_multiple_optional_tags_use_matches_any(): void
    {
        $context = [
            'payload' => [
                'tags' => [
                    'renewal',
                ],
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'vip'
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'renewal'
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAny(
                $context,
                $conditions
            )
        );
    }

    public function test_lead_source_can_define_segment(): void
    {
        $context = [
            'payload' => [
                'source' =>
                    LeadSource::WEBSITE,
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.source',
                    ConditionOperator::EQUALS,
                    'website'
                )
            )
        );
    }

    public function test_lead_source_group_can_define_segment(): void
    {
        $context = [
            'payload' => [
                'source' => 'referral',
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.source',
                    ConditionOperator::IN,
                    [
                        LeadSource::REFERRAL,
                        LeadSource::WEBSITE,
                    ]
                )
            )
        );
    }

    public function test_status_can_participate_in_segment(): void
    {
        $context = [
            'payload' => [
                'status' =>
                    LeadStatus::QUALIFIED,

                'tags' => [
                    'enterprise',
                ],
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.status',
                ConditionOperator::EQUALS,
                LeadStatus::QUALIFIED
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'enterprise'
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_customer_type_can_define_segment(): void
    {
        $context = [
            'payload' => [
                'customer' => [
                    'type' =>
                        CustomerType::COMPANY,
                ],
            ],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.customer.type',
                    ConditionOperator::EQUALS,
                    'company'
                )
            )
        );
    }

    public function test_segment_can_combine_type_tags_and_value(): void
    {
        $context = [
            'payload' => [
                'customer' => [
                    'type' => 'company',

                    'tags' => [
                        'vip',
                        'enterprise',
                    ],
                ],

                'value_minor' =>
                    250000,
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.customer.type',
                ConditionOperator::EQUALS,
                CustomerType::COMPANY
            ),

            AutomationCondition::make(
                'payload.customer.tags',
                ConditionOperator::CONTAINS,
                'vip'
            ),

            AutomationCondition::make(
                'payload.value_minor',
                ConditionOperator::GREATER_THAN_OR_EQUAL,
                100000
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_segment_can_combine_status_source_responsible_and_tags(): void
    {
        $context = [
            'tenant_id' => 50,

            'payload' => [
                'status' =>
                    LeadStatus::QUALIFIED,

                'source' =>
                    LeadSource::REFERRAL,

                'responsible_user_id' =>
                    99,

                'tags' => [
                    'enterprise',
                    'priority',
                ],
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'tenant_id',
                ConditionOperator::EQUALS,
                50
            ),

            AutomationCondition::make(
                'payload.status',
                ConditionOperator::EQUALS,
                LeadStatus::QUALIFIED
            ),

            AutomationCondition::make(
                'payload.source',
                ConditionOperator::EQUALS,
                LeadSource::REFERRAL
            ),

            AutomationCondition::make(
                'payload.responsible_user_id',
                ConditionOperator::EQUALS,
                99
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'priority'
            ),
        ];

        $this->assertTrue(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_segment_fails_when_one_required_dimension_fails(): void
    {
        $context = [
            'payload' => [
                'status' =>
                    LeadStatus::CONTACTED,

                'source' =>
                    LeadSource::WEBSITE,

                'tags' => [
                    'vip',
                ],
            ],
        ];

        $conditions = [
            AutomationCondition::make(
                'payload.status',
                ConditionOperator::EQUALS,
                LeadStatus::QUALIFIED
            ),

            AutomationCondition::make(
                'payload.source',
                ConditionOperator::EQUALS,
                LeadSource::WEBSITE
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'vip'
            ),
        ];

        $this->assertFalse(
            $this->evaluator->matchesAll(
                $context,
                $conditions
            )
        );
    }

    public function test_empty_tag_list_does_not_match_contains(): void
    {
        $context = [
            'payload' => [
                'tags' => [],
            ],
        ];

        $this->assertFalse(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.tags',
                    ConditionOperator::CONTAINS,
                    'vip'
                )
            )
        );
    }

    public function test_missing_tags_can_be_detected_as_null(): void
    {
        $context = [
            'payload' => [],
        ];

        $this->assertTrue(
            $this->evaluator->matches(
                $context,
                AutomationCondition::make(
                    'payload.tags',
                    ConditionOperator::IS_NULL
                )
            )
        );
    }

    public function test_trigger_context_normalizes_segmentation_enums(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::CUSTOM,

            tenantId:
                80,

            payload: [
                'status' =>
                    LeadStatus::QUALIFIED,

                'source' =>
                    LeadSource::SOCIAL,

                'customer' => [
                    'type' =>
                        CustomerType::COMPANY,
                ],

                'tags' => [
                    'vip',
                    'social',
                ],
            ],

            customName:
                'lead.segmented',
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $this->assertSame(
            'qualified',
            $context['payload']['status']
        );

        $this->assertSame(
            'social',
            $context['payload']['source']
        );

        $this->assertSame(
            'company',
            $context[
                'payload'
            ][
                'customer'
            ][
                'type'
            ]
        );

        $this->assertSame(
            [
                'vip',
                'social',
            ],
            $context['payload']['tags']
        );
    }

    public function test_trigger_context_can_be_segmented_directly(): void
    {
        $occurrence = new TriggerOccurrence(
            type:
                TriggerType::LEAD_CREATED,

            tenantId:
                7,

            payload: [
                'status' =>
                    LeadStatus::QUALIFIED,

                'source' =>
                    LeadSource::WEBSITE,

                'tags' => [
                    'enterprise',
                ],
            ],
        );

        $context =
            (new TriggerConditionContext())
                ->make(
                    $occurrence
                );

        $conditions = [
            AutomationCondition::make(
                'trigger.name',
                ConditionOperator::EQUALS,
                'lead.created'
            ),

            AutomationCondition::make(
                'payload.status',
                ConditionOperator::EQUALS,
                'qualified'
            ),

            AutomationCondition::make(
                'payload.tags',
                ConditionOperator::CONTAINS,
                'enterprise'
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