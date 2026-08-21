<?php

namespace Database\Seeders;

use App\Enums\Feature;
use App\Models\Plan;
use App\Support\PlanCatalog;
use App\Support\PlanCommercialPolicy;
use Illuminate\Database\Seeder;

class PlanCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $policies =
            PlanCommercialPolicy::definitions();

        foreach (
            PlanCatalog::definitions()
            as $definition
        ) {
            $plan = Plan::query()->updateOrCreate(
                [
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'active' => true,
                ]
            );

            $policy =
                $policies[$plan->code];

            foreach (Feature::cases() as $feature) {
                $enabled = in_array(
                    $feature,
                    $policy['features'],
                    true
                );

                $limit =
                    $policy['limits'][
                        $feature->value
                    ] ?? null;

                $plan->features()->updateOrCreate(
                    [
                        'feature' =>
                            $feature->value,
                    ],
                    [
                        'enabled' =>
                            $enabled,

                        'limit_value' =>
                            $limit,
                    ]
                );
            }

            $usageMetrics =
                array_keys(
                    $policy['usage_limits']
                );

            if ($usageMetrics === []) {
                $plan->usageLimits()->delete();
            } else {
                $plan->usageLimits()
                    ->whereNotIn(
                        'metric',
                        $usageMetrics
                    )
                    ->delete();
            }

            foreach (
                $policy['usage_limits']
                as $metric => $limit
            ) {
                $plan->usageLimits()->updateOrCreate(
                    [
                        'metric' =>
                            $metric,
                    ],
                    [
                        'limit_value' =>
                            $limit,
                    ]
                );
            }
            $currencies =
                array_keys(
                    $policy['prices']
                );

            if ($currencies === []) {
                $plan->prices()->delete();
            } else {
                $plan->prices()
                    ->whereNotIn(
                        'currency',
                        $currencies
                    )
                    ->delete();
            }

            foreach (
                $policy['prices']
                as $currency => $amount
            ) {
                $plan->prices()->updateOrCreate(
                    [
                        'currency' =>
                            $currency,
                    ],
                    [
                        'amount_minor' =>
                            $amount,
                    ]
                );
            }
        }
    }
}