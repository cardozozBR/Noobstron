<?php

namespace App\Services;

use App\Models\Plan;

class PlanCapabilityProfile
{
    public function definitions(
        Plan $plan
    ): array {
        return $plan->features()
            ->orderBy('feature')
            ->get()
            ->map(
                fn ($feature): array => [
                    'feature' =>
                        $feature->feature,

                    'enabled' =>
                        $feature->enabled,

                    'limit' =>
                        $feature->limit_value,
                ]
            )
            ->all();
    }
}