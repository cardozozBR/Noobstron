<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_has_features_and_prices(): void
    {
        $plan = $this->plan();

        $feature = $plan->features()->create([
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 3,
        ]);

        $price = $plan->prices()->create([
            'currency' => 'BRL',
            'amount_minor' => 9900,
        ]);

        $this->assertSame(
            Feature::USERS,
            $feature->feature
        );

        $this->assertSame(
            3,
            $feature->limit_value
        );

        $this->assertSame(
            'BRL',
            $price->currency
        );

        $this->assertSame(
            9900,
            $price->amount_minor
        );
    }

    public function test_plan_code_and_name_are_normalized(): void
    {
        $plan = Plan::query()->create([
            'code' => '  START  ',
            'name' => '  Start  ',
        ]);

        $this->assertSame(
            'start',
            $plan->code
        );

        $this->assertSame(
            'Start',
            $plan->name
        );
    }

    public function test_blank_plan_code_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        Plan::query()->create([
            'code' => '   ',
            'name' => 'Start',
        ]);
    }

    public function test_blank_plan_name_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        Plan::query()->create([
            'code' => 'start',
            'name' => '   ',
        ]);
    }

    public function test_invalid_plan_code_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        Plan::query()->create([
            'code' => 'Start Plan!',
            'name' => 'Start',
        ]);
    }

    public function test_negative_feature_limit_is_rejected(): void
    {
        $plan = $this->plan();

        $this->expectException(
            InvalidArgumentException::class
        );

        PlanFeature::query()->create([
            'plan_id' => $plan->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => -1,
        ]);
    }

    public function test_zero_feature_limit_is_supported(): void
    {
        $plan = $this->plan();

        $feature = PlanFeature::query()->create([
            'plan_id' => $plan->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 0,
        ]);

        $this->assertSame(
            0,
            $feature->limit_value
        );
    }

    public function test_currency_is_normalized(): void
    {
        $plan = $this->plan();

        $price = PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => ' brl ',
            'amount_minor' => 9900,
        ]);

        $this->assertSame(
            'BRL',
            $price->currency
        );
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $plan = $this->plan();

        $this->expectException(
            InvalidArgumentException::class
        );

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'ABC',
            'amount_minor' => 9900,
        ]);
    }

    public function test_negative_price_is_rejected(): void
    {
        $plan = $this->plan();

        $this->expectException(
            InvalidArgumentException::class
        );

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => -1,
        ]);
    }

    private function plan(): Plan
    {
        return Plan::query()->create([
            'code' => 'start',
            'name' => 'Start',
            'active' => true,
        ]);
    }
}