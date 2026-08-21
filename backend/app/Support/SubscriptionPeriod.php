<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class SubscriptionPeriod
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {
        if ($this->endsAt->lessThanOrEqualTo($this->startsAt)) {
            throw new InvalidArgumentException(
                'Subscription end must be after start.'
            );
        }
    }

    public function contains(
        CarbonImmutable $moment
    ): bool {
        return $moment->greaterThanOrEqualTo(
            $this->startsAt
        ) && $moment->lessThan(
            $this->endsAt
        );
    }

    public function isExpiredAt(
        CarbonImmutable $moment
    ): bool {
        return $moment->greaterThanOrEqualTo(
            $this->endsAt
        );
    }
}