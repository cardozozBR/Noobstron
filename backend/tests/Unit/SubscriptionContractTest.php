<?php

namespace Tests\Unit;

use App\Enums\SubscriptionStatus;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SubscriptionContractTest extends TestCase
{
    public function test_subscription_statuses_are_defined(): void
    {
        $this->assertSame(
            [
                'active',
                'cancelled',
                'suspended',
                'expired',
            ],
            array_map(
                static fn (SubscriptionStatus $status): string =>
                    $status->value,
                SubscriptionStatus::cases()
            )
        );
    }

    public function test_only_active_status_is_active(): void
    {
        $this->assertTrue(
            SubscriptionStatus::ACTIVE->isActive()
        );

        $this->assertFalse(
            SubscriptionStatus::CANCELLED->isActive()
        );

        $this->assertFalse(
            SubscriptionStatus::SUSPENDED->isActive()
        );

        $this->assertFalse(
            SubscriptionStatus::EXPIRED->isActive()
        );
    }

    public function test_cancelled_and_expired_are_terminal(): void
    {
        $this->assertFalse(
            SubscriptionStatus::ACTIVE->isTerminal()
        );

        $this->assertFalse(
            SubscriptionStatus::SUSPENDED->isTerminal()
        );

        $this->assertTrue(
            SubscriptionStatus::CANCELLED->isTerminal()
        );

        $this->assertTrue(
            SubscriptionStatus::EXPIRED->isTerminal()
        );
    }

    public function test_period_is_active_inside_boundaries(): void
    {
        $period = $this->period();

        $this->assertTrue(
            $period->contains(
                CarbonImmutable::parse(
                    '2026-08-18 12:00:00 UTC'
                )
            )
        );
    }

    public function test_period_includes_start(): void
    {
        $period = $this->period();

        $this->assertTrue(
            $period->contains($period->startsAt)
        );
    }

    public function test_period_excludes_end(): void
    {
        $period = $this->period();

        $this->assertFalse(
            $period->contains($period->endsAt)
        );

        $this->assertTrue(
            $period->isExpiredAt($period->endsAt)
        );
    }

    public function test_period_rejects_equal_dates(): void
    {
        $moment = CarbonImmutable::parse(
            '2026-08-18 00:00:00 UTC'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        new SubscriptionPeriod(
            $moment,
            $moment
        );
    }

    public function test_period_rejects_end_before_start(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new SubscriptionPeriod(
            CarbonImmutable::parse(
                '2026-08-19 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-08-18 00:00:00 UTC'
            )
        );
    }

    private function period(): SubscriptionPeriod
    {
        return new SubscriptionPeriod(
            CarbonImmutable::parse(
                '2026-08-18 00:00:00 UTC'
            ),
            CarbonImmutable::parse(
                '2026-09-18 00:00:00 UTC'
            )
        );
    }
}