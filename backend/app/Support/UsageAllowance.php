<?php

namespace App\Support;

use App\Models\Plan;
use InvalidArgumentException;

final readonly class UsageAllowance
{
    private function __construct(
        public bool $available,
        public bool $allowed,
        public bool $unlimited,
        public int $used,
        public ?int $limit,
        public ?int $remaining,
        public bool $exceeded,
        public bool $upgradeSuggested,
        public ?Plan $plan,
    ) {
        if ($this->used < 0) {
            throw new InvalidArgumentException(
                'Usage cannot be negative.'
            );
        }

        if (! $this->available) {
            if (
                $this->allowed
                || $this->unlimited
                || $this->limit !== null
                || $this->remaining !== null
                || $this->exceeded
                || $this->upgradeSuggested
            ) {
                throw new InvalidArgumentException(
                    'Unavailable allowance has invalid state.'
                );
            }

            return;
        }

        if ($this->unlimited) {
            if (
                ! $this->allowed
                || $this->limit !== null
                || $this->remaining !== null
                || $this->exceeded
                || $this->upgradeSuggested
            ) {
                throw new InvalidArgumentException(
                    'Unlimited allowance has invalid state.'
                );
            }

            return;
        }

        if ($this->limit === null) {
            throw new InvalidArgumentException(
                'Limited allowance requires a limit.'
            );
        }

        if ($this->limit < 0) {
            throw new InvalidArgumentException(
                'Usage limit cannot be negative.'
            );
        }

        if ($this->remaining === null) {
            throw new InvalidArgumentException(
                'Limited allowance requires remaining usage.'
            );
        }

        if ($this->remaining < 0) {
            throw new InvalidArgumentException(
                'Remaining usage cannot be negative.'
            );
        }
    }

    public static function unavailable(
        int $used,
        ?Plan $plan = null
    ): self {
        return new self(
            available: false,
            allowed: false,
            unlimited: false,
            used: $used,
            limit: null,
            remaining: null,
            exceeded: false,
            upgradeSuggested: false,
            plan: $plan,
        );
    }

    public static function unlimited(
        int $used,
        Plan $plan
    ): self {
        return new self(
            available: true,
            allowed: true,
            unlimited: true,
            used: $used,
            limit: null,
            remaining: null,
            exceeded: false,
            upgradeSuggested: false,
            plan: $plan,
        );
    }

    public static function limited(
        int $used,
        int $limit,
        Plan $plan
    ): self {
        $remaining = max(
            0,
            $limit - $used
        );

        $exceeded =
            $used >= $limit;

        return new self(
            available: true,
            allowed: ! $exceeded,
            unlimited: false,
            used: $used,
            limit: $limit,
            remaining: $remaining,
            exceeded: $exceeded,
            upgradeSuggested: $exceeded,
            plan: $plan,
        );
    }
}