<?php

namespace App\Support;

use App\Enums\Feature;
use App\Enums\UsageMetric;

final class PlanCommercialPolicy
{
    public static function definitions(): array
    {
        return [
            'start' => [
                'features' => [
                    Feature::USERS,
                    Feature::LEADS,
                    Feature::CUSTOMERS,
                    Feature::PIPELINES,
                    Feature::OPPORTUNITIES,
                    Feature::ACTIVITIES,
                    Feature::AI,
                ],
                'limits' => [
                    Feature::USERS->value => 3,
                ],
                'prices' => [
                    'BRL' => 9900,
                ],
                'usage_limits' => [
                    UsageMetric::STORAGE_BYTES->value =>
                        1_073_741_824,

                    UsageMetric::MESSAGES->value =>
                        1_000,

                    UsageMetric::AI_TOKENS->value =>
                        100_000,
                ],
            ],

            'pro' => [
                'features' => [
                    Feature::USERS,
                    Feature::LEADS,
                    Feature::CUSTOMERS,
                    Feature::PIPELINES,
                    Feature::OPPORTUNITIES,
                    Feature::ACTIVITIES,
                    Feature::AI,
                    Feature::CATALOG,
                    Feature::PROPOSALS,
                    Feature::SALES,
                    Feature::EMAIL,
                ],
                'limits' => [
                    Feature::USERS->value => 10,
                ],
                'prices' => [
                    'BRL' => 24900,
                ],
                'usage_limits' => [
                    UsageMetric::STORAGE_BYTES->value =>
                        10_737_418_240,

                    UsageMetric::MESSAGES->value =>
                        10_000,

                    UsageMetric::AI_TOKENS->value =>
                        1_000_000,
                ],
            ],

            'business' => [
                'features' => Feature::cases(),
                'limits' => [
                    Feature::USERS->value => 30,
                ],
                'prices' => [
                    'BRL' => 49900,
                ],
                'usage_limits' => [
                    UsageMetric::STORAGE_BYTES->value =>
                        53_687_091_200,

                    UsageMetric::MESSAGES->value =>
                        50_000,

                    UsageMetric::AI_TOKENS->value =>
                        5_000_000,
                ],
            ],

            'enterprise' => [
                'features' => Feature::cases(),
                'limits' => [
                    Feature::USERS->value => null,
                ],
                'prices' => [],
                'usage_limits' => [
                    UsageMetric::STORAGE_BYTES->value =>
                        null,

                    UsageMetric::MESSAGES->value =>
                        null,

                    UsageMetric::AI_TOKENS->value =>
                        null,
                ],
            ],
        ];
    }
}