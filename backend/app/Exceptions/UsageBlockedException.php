<?php

namespace App\Exceptions;

use App\Enums\UsageMetric;
use App\Models\Plan;
use RuntimeException;

class UsageBlockedException extends RuntimeException
{
    public function __construct(
        public readonly UsageMetric $metric,
        public readonly string $reason,
        public readonly int $used,
        public readonly int $requested,
        public readonly ?int $limit,
        public readonly ?int $remaining,
        public readonly bool $upgradeSuggested,
        public readonly ?Plan $plan,
    ) {
        parent::__construct(
            $reason === 'limit_exceeded'
                ? 'Usage limit exceeded.'
                : 'Usage limit is unavailable.'
        );
    }

    public static function unavailable(
        UsageMetric $metric,
        int $used,
        int $requested,
        ?Plan $plan = null,
    ): self {
        return new self(
            metric: $metric,
            reason: 'unavailable',
            used: $used,
            requested: $requested,
            limit: null,
            remaining: null,
            upgradeSuggested: false,
            plan: $plan,
        );
    }

    public static function exceeded(
        UsageMetric $metric,
        int $used,
        int $requested,
        int $limit,
        int $remaining,
        Plan $plan,
    ): self {
        return new self(
            metric: $metric,
            reason: 'limit_exceeded',
            used: $used,
            requested: $requested,
            limit: $limit,
            remaining: $remaining,
            upgradeSuggested: true,
            plan: $plan,
        );
    }
}