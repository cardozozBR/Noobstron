<?php

namespace Tests\Unit;

use App\Enums\Feature;
use App\Enums\UsageMetric;
use App\Support\PlanCommercialPolicy;
use PHPUnit\Framework\TestCase;

class PlanCommercialPolicyTest extends TestCase
{
    public function test_expected_user_limits(): void
    {
        $plans =
            PlanCommercialPolicy::definitions();

        $this->assertSame(
            3,
            $plans['start']['limits']['users']
        );

        $this->assertSame(
            10,
            $plans['pro']['limits']['users']
        );

        $this->assertSame(
            30,
            $plans['business']['limits']['users']
        );

        $this->assertNull(
            $plans['enterprise']['limits']['users']
        );
    }

    public function test_expected_brl_prices(): void
    {
        $plans =
            PlanCommercialPolicy::definitions();

        $this->assertSame(
            9900,
            $plans['start']['prices']['BRL']
        );

        $this->assertSame(
            24900,
            $plans['pro']['prices']['BRL']
        );

        $this->assertSame(
            49900,
            $plans['business']['prices']['BRL']
        );

        $this->assertSame(
            [],
            $plans['enterprise']['prices']
        );
    }

    public function test_expected_usage_limits(): void
    {
        $plans =
            PlanCommercialPolicy::definitions();

        $this->assertSame(
            1_073_741_824,
            $plans['start']['usage_limits'][
                UsageMetric::STORAGE_BYTES->value
            ]
        );

        $this->assertSame(
            1_000,
            $plans['start']['usage_limits'][
                UsageMetric::MESSAGES->value
            ]
        );

        $this->assertSame(
            100_000,
            $plans['start']['usage_limits'][
                UsageMetric::AI_TOKENS->value
            ]
        );

        $this->assertSame(
            10_737_418_240,
            $plans['pro']['usage_limits'][
                UsageMetric::STORAGE_BYTES->value
            ]
        );

        $this->assertSame(
            10_000,
            $plans['pro']['usage_limits'][
                UsageMetric::MESSAGES->value
            ]
        );

        $this->assertSame(
            1_000_000,
            $plans['pro']['usage_limits'][
                UsageMetric::AI_TOKENS->value
            ]
        );

        $this->assertSame(
            53_687_091_200,
            $plans['business']['usage_limits'][
                UsageMetric::STORAGE_BYTES->value
            ]
        );

        $this->assertSame(
            50_000,
            $plans['business']['usage_limits'][
                UsageMetric::MESSAGES->value
            ]
        );

        $this->assertSame(
            5_000_000,
            $plans['business']['usage_limits'][
                UsageMetric::AI_TOKENS->value
            ]
        );

        $this->assertNull(
            $plans['enterprise']['usage_limits'][
                UsageMetric::STORAGE_BYTES->value
            ]
        );

        $this->assertNull(
            $plans['enterprise']['usage_limits'][
                UsageMetric::MESSAGES->value
            ]
        );

        $this->assertNull(
            $plans['enterprise']['usage_limits'][
                UsageMetric::AI_TOKENS->value
            ]
        );
    }

    public function test_business_and_enterprise_enable_all_features(): void
    {
        $plans =
            PlanCommercialPolicy::definitions();

        $this->assertSame(
            Feature::cases(),
            $plans['business']['features']
        );

        $this->assertSame(
            Feature::cases(),
            $plans['enterprise']['features']
        );
    }

    public function test_ai_feature_is_available_in_all_official_plans(): void
    {
        $plans =
            PlanCommercialPolicy::definitions();

        foreach ([
            'start',
            'pro',
            'business',
            'enterprise',
        ] as $code) {
            $this->assertContains(
                Feature::AI,
                $plans[$code]['features'],
                "AI feature missing from plan [{$code}]."
            );
        }
    }}