<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class TrialPeriod
{
    public const DEFAULT_DAYS = 14;

    public function __construct(
        private readonly CarbonImmutable $startsAt,
        private readonly CarbonImmutable $endsAt,
    ) {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException(
                'Trial end must be after trial start.'
            );
        }
    }

    public static function start(
        CarbonImmutable $startsAt,
        int $days = self::DEFAULT_DAYS,
    ): self {
        if ($days <= 0) {
            throw new InvalidArgumentException(
                'Trial duration must be greater than zero.'
            );
        }

        return new self(
            $startsAt,
            $startsAt->addDays($days),
        );
    }

    public function startsAt(): CarbonImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): CarbonImmutable
    {
        return $this->endsAt;
    }

    public function isStartedAt(CarbonImmutable $moment): bool
    {
        return $moment->greaterThanOrEqualTo($this->startsAt);
    }

    public function isExpiredAt(CarbonImmutable $moment): bool
    {
        return $moment->greaterThanOrEqualTo($this->endsAt);
    }

    public function isActiveAt(CarbonImmutable $moment): bool
    {
        return $this->isStartedAt($moment)
            && ! $this->isExpiredAt($moment);
    }
}
