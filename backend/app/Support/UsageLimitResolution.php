<?php

namespace App\Support;

use App\Models\Plan;
use InvalidArgumentException;

final readonly class UsageLimitResolution
{
    private function __construct(
        public bool $available,
        public bool $unlimited,
        public ?int $limit,
        public ?Plan $plan,
    ) {
        if (! $this->available) {
            if (
                $this->unlimited
                || $this->limit !== null
            ) {
                throw new InvalidArgumentException(
                    'Unavailable usage limit cannot define a limit.'
                );
            }

            return;
        }

        if ($this->unlimited) {
            if ($this->limit !== null) {
                throw new InvalidArgumentException(
                    'Unlimited usage cannot define a numeric limit.'
                );
            }

            return;
        }

        if ($this->limit === null) {
            throw new InvalidArgumentException(
                'Limited usage requires a numeric limit.'
            );
        }

        if ($this->limit < 0) {
            throw new InvalidArgumentException(
                'Usage limit cannot be negative.'
            );
        }
    }

    public static function unavailable(
        ?Plan $plan = null
    ): self {
        return new self(
            available: false,
            unlimited: false,
            limit: null,
            plan: $plan,
        );
    }

    public static function unlimited(
        Plan $plan
    ): self {
        return new self(
            available: true,
            unlimited: true,
            limit: null,
            plan: $plan,
        );
    }

    public static function limited(
        Plan $plan,
        int $limit
    ): self {
        return new self(
            available: true,
            unlimited: false,
            limit: $limit,
            plan: $plan,
        );
    }

    public function isLimited(): bool
    {
        return $this->available
            && ! $this->unlimited;
    }
}