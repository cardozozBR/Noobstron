<?php

namespace App\Services;

use App\Models\Plan;
use App\Support\Currency;
use App\Support\Money;
use RuntimeException;

class PlanPriceResolver
{
    public function resolve(
        Plan $plan,
        string $currency
    ): Money {
        $currency = Currency::normalize(
            $currency
        );

        $price = $plan->prices()
            ->where(
                'currency',
                $currency
            )
            ->first();

        if ($price === null) {
            throw new RuntimeException(
                sprintf(
                    'No price configured for plan %s in currency %s.',
                    $plan->code,
                    $currency
                )
            );
        }

        return Money::fromMinor(
            $price->amount_minor,
            $price->currency
        );
    }
}