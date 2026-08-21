<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\UsageMetric;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use App\Models\PlanUsageLimit;
use Database\Seeders\PlanCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_plans_and_commercial_policy_are_seeded(): void
    {
        $this->seed(
            PlanCatalogSeeder::class
        );

        $this->assertSame(
            4,
            Plan::query()->count()
        );

        $start = Plan::query()
            ->where('code', 'start')
            ->firstOrFail();

        $pro = Plan::query()
            ->where('code', 'pro')
            ->firstOrFail();

        $business = Plan::query()
            ->where('code', 'business')
            ->firstOrFail();

        $enterprise = Plan::query()
            ->where('code', 'enterprise')
            ->firstOrFail();

        $this->assertSame(
            19,
            $start->features()->count()
        );

        $this->assertSame(
            3,
            $start->features()
                ->where(
                    'feature',
                    Feature::USERS->value
                )
                ->firstOrFail()
                ->limit_value
        );

        $this->assertSame(
            10,
            $pro->features()
                ->where(
                    'feature',
                    Feature::USERS->value
                )
                ->firstOrFail()
                ->limit_value
        );

        $this->assertSame(
            30,
            $business->features()
                ->where(
                    'feature',
                    Feature::USERS->value
                )
                ->firstOrFail()
                ->limit_value
        );

        $this->assertNull(
            $enterprise->features()
                ->where(
                    'feature',
                    Feature::USERS->value
                )
                ->firstOrFail()
                ->limit_value
        );

        $this->assertDatabaseHas(
            'plan_prices',
            [
                'plan_id' => $start->id,
                'currency' => 'BRL',
                'amount_minor' => 9900,
            ]
        );

        $this->assertDatabaseHas(
            'plan_prices',
            [
                'plan_id' => $pro->id,
                'currency' => 'BRL',
                'amount_minor' => 24900,
            ]
        );

        $this->assertDatabaseHas(
            'plan_prices',
            [
                'plan_id' => $business->id,
                'currency' => 'BRL',
                'amount_minor' => 49900,
            ]
        );

        $this->assertSame(
            0,
            $enterprise->prices()->count()
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $start->id,
                'metric' =>
                    UsageMetric::STORAGE_BYTES->value,
                'limit_value' =>
                    1_073_741_824,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $start->id,
                'metric' =>
                    UsageMetric::MESSAGES->value,
                'limit_value' => 1_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $start->id,
                'metric' =>
                    UsageMetric::AI_TOKENS->value,
                'limit_value' => 100_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $pro->id,
                'metric' =>
                    UsageMetric::STORAGE_BYTES->value,
                'limit_value' =>
                    10_737_418_240,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $pro->id,
                'metric' =>
                    UsageMetric::MESSAGES->value,
                'limit_value' => 10_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $pro->id,
                'metric' =>
                    UsageMetric::AI_TOKENS->value,
                'limit_value' => 1_000_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $business->id,
                'metric' =>
                    UsageMetric::STORAGE_BYTES->value,
                'limit_value' =>
                    53_687_091_200,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $business->id,
                'metric' =>
                    UsageMetric::MESSAGES->value,
                'limit_value' => 50_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $business->id,
                'metric' =>
                    UsageMetric::AI_TOKENS->value,
                'limit_value' => 5_000_000,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $enterprise->id,
                'metric' =>
                    UsageMetric::STORAGE_BYTES->value,
                'limit_value' => null,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $enterprise->id,
                'metric' =>
                    UsageMetric::MESSAGES->value,
                'limit_value' => null,
            ]
        );

        $this->assertDatabaseHas(
            'plan_usage_limits',
            [
                'plan_id' => $enterprise->id,
                'metric' =>
                    UsageMetric::AI_TOKENS->value,
                'limit_value' => null,
            ]
        );

        $this->assertSame(
            3,
            $start->usageLimits()->count()
        );

        $this->assertSame(
            3,
            $pro->usageLimits()->count()
        );

        $this->assertSame(
            3,
            $business->usageLimits()->count()
        );

        $this->assertSame(
            3,
            $enterprise->usageLimits()->count()
        );
    }

    public function test_plan_catalog_seeder_is_idempotent(): void
    {
        $this->seed(
            PlanCatalogSeeder::class
        );

        $this->seed(
            PlanCatalogSeeder::class
        );

        $this->assertSame(
            4,
            Plan::query()->count()
        );

        $this->assertSame(
            76,
            PlanFeature::query()->count()
        );

        $this->assertSame(
            3,
            PlanPrice::query()->count()
        );

        $this->assertSame(
            12,
            PlanUsageLimit::query()->count()
        );
    }
}