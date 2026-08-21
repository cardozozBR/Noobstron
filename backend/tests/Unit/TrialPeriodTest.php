<?php

namespace Tests\Unit;

use App\Support\TrialPeriod;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TrialPeriodTest extends TestCase
{
    public function test_default_trial_has_fourteen_days(): void
    {
        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $trial = TrialPeriod::start($start);

        $this->assertTrue(
            $trial->startsAt()->equalTo($start)
        );

        $this->assertTrue(
            $trial->endsAt()->equalTo(
                $start->addDays(14)
            )
        );
    }

    public function test_custom_trial_duration_is_supported(): void
    {
        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $trial = TrialPeriod::start($start, 30);

        $this->assertTrue(
            $trial->endsAt()->equalTo(
                $start->addDays(30)
            )
        );
    }

    public function test_trial_is_active_inside_period(): void
    {
        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $trial = TrialPeriod::start($start);

        $this->assertTrue(
            $trial->isActiveAt(
                $start->addDay()
            )
        );
    }

    public function test_trial_is_not_active_before_start(): void
    {
        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $trial = TrialPeriod::start($start);

        $this->assertFalse(
            $trial->isActiveAt(
                $start->subSecond()
            )
        );
    }

    public function test_trial_expires_exactly_at_end(): void
    {
        $start = CarbonImmutable::parse(
            '2026-08-17 12:00:00',
            'UTC'
        );

        $trial = TrialPeriod::start($start);

        $this->assertTrue(
            $trial->isExpiredAt(
                $trial->endsAt()
            )
        );

        $this->assertFalse(
            $trial->isActiveAt(
                $trial->endsAt()
            )
        );
    }

    public function test_zero_duration_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TrialPeriod::start(
            CarbonImmutable::now('UTC'),
            0
        );
    }

    public function test_negative_duration_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TrialPeriod::start(
            CarbonImmutable::now('UTC'),
            -1
        );
    }
}
