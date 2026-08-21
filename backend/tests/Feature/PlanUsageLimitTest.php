<?php

namespace Tests\Feature;

use App\Enums\UsageMetric;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PlanUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_limit_belongs_to_plan(): void
    {
        $plan = $this->plan('usage-start');

        $limit = PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::STORAGE_BYTES,
            'limit_value' => 1000,
        ]);

        $this->assertTrue(
            $limit->plan->is($plan)
        );

        $this->assertTrue(
            $plan->usageLimits()
                ->where(
                    'metric',
                    UsageMetric::STORAGE_BYTES->value
                )
                ->firstOrFail()
                ->is($limit)
        );
    }

    public function test_metric_is_cast_to_usage_metric_enum(): void
    {
        $limit = PlanUsageLimit::query()->create([
            'plan_id' => $this->plan('usage-enum')->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 500,
        ]);

        $this->assertSame(
            UsageMetric::MESSAGES,
            $limit->metric
        );
    }

    public function test_positive_limit_is_supported(): void
    {
        $limit = PlanUsageLimit::query()->create([
            'plan_id' => $this->plan('usage-positive')->id,
            'metric' => UsageMetric::AI_TOKENS,
            'limit_value' => 10000,
        ]);

        $this->assertSame(
            10000,
            $limit->limit_value
        );
    }

    public function test_zero_limit_is_supported(): void
    {
        $limit = PlanUsageLimit::query()->create([
            'plan_id' => $this->plan('usage-zero')->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 0,
        ]);

        $this->assertSame(
            0,
            $limit->limit_value
        );
    }

    public function test_null_limit_represents_unlimited_usage(): void
    {
        $limit = PlanUsageLimit::query()->create([
            'plan_id' => $this->plan('usage-unlimited')->id,
            'metric' => UsageMetric::STORAGE_BYTES,
            'limit_value' => null,
        ]);

        $this->assertNull(
            $limit->limit_value
        );
    }

    public function test_negative_limit_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $this->plan('usage-negative')->id,
            'metric' => UsageMetric::AI_TOKENS,
            'limit_value' => -1,
        ]);
    }

    public function test_same_metric_cannot_be_duplicated_in_plan(): void
    {
        $plan = $this->plan('usage-unique');

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 100,
        ]);

        $this->expectException(
            QueryException::class
        );

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 200,
        ]);
    }

    public function test_same_metric_can_have_different_limits_between_plans(): void
    {
        $start = $this->plan('usage-start-isolated');
        $pro = $this->plan('usage-pro-isolated');

        PlanUsageLimit::query()->create([
            'plan_id' => $start->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 100,
        ]);

        PlanUsageLimit::query()->create([
            'plan_id' => $pro->id,
            'metric' => UsageMetric::MESSAGES,
            'limit_value' => 500,
        ]);

        $this->assertSame(
            100,
            $start->usageLimits()
                ->where(
                    'metric',
                    UsageMetric::MESSAGES->value
                )
                ->value('limit_value')
        );

        $this->assertSame(
            500,
            $pro->usageLimits()
                ->where(
                    'metric',
                    UsageMetric::MESSAGES->value
                )
                ->value('limit_value')
        );
    }

    private function plan(string $code): Plan
    {
        return Plan::query()->create([
            'code' => $code,
            'name' => ucfirst($code),
            'active' => true,
        ]);
    }
}