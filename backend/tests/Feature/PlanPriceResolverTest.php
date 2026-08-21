<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\PlanPriceResolver;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PlanPriceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_is_resolved_as_money(): void
    {
        $plan = $this->plan();

        $plan->prices()->create([
            'currency' => 'BRL',
            'amount_minor' => 9900,
        ]);

        $money = app(
            PlanPriceResolver::class
        )->resolve(
            $plan,
            'BRL'
        );

        $this->assertInstanceOf(
            Money::class,
            $money
        );

        $this->assertSame(
            9900,
            $money->minor
        );

        $this->assertSame(
            'BRL',
            $money->currency
        );
    }

    public function test_currency_input_is_normalized(): void
    {
        $plan = $this->plan();

        $plan->prices()->create([
            'currency' => 'USD',
            'amount_minor' => 1900,
        ]);

        $money = app(
            PlanPriceResolver::class
        )->resolve(
            $plan,
            ' usd '
        );

        $this->assertSame(
            'USD',
            $money->currency
        );
    }

    public function test_missing_currency_price_is_rejected(): void
    {
        $plan = $this->plan();

        $this->expectException(
            RuntimeException::class
        );

        app(
            PlanPriceResolver::class
        )->resolve(
            $plan,
            'EUR'
        );
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $plan = $this->plan();

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            PlanPriceResolver::class
        )->resolve(
            $plan,
            'ABC'
        );
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