<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Plan;
use App\Services\PlanCapabilityProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCapabilityProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_features_are_translated_to_capability_profile(): void
    {
        $plan = Plan::query()->create([
            'code' => 'start',
            'name' => 'Start',
            'active' => true,
        ]);

        $plan->features()->create([
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 3,
        ]);

        $plan->features()->create([
            'feature' => Feature::AUDIT,
            'enabled' => false,
            'limit_value' => null,
        ]);

        $definitions = app(
            PlanCapabilityProfile::class
        )->definitions(
            $plan
        );

        $this->assertCount(
            2,
            $definitions
        );

        $this->assertContains(
            [
                'feature' => Feature::USERS,
                'enabled' => true,
                'limit' => 3,
            ],
            $definitions
        );

        $this->assertContains(
            [
                'feature' => Feature::AUDIT,
                'enabled' => false,
                'limit' => null,
            ],
            $definitions
        );
    }

    public function test_empty_plan_has_empty_profile(): void
    {
        $plan = Plan::query()->create([
            'code' => 'enterprise',
            'name' => 'Enterprise',
            'active' => true,
        ]);

        $this->assertSame(
            [],
            app(
                PlanCapabilityProfile::class
            )->definitions(
                $plan
            )
        );
    }
}