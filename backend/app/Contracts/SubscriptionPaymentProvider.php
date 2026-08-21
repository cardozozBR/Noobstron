<?php

namespace App\Contracts;

use App\Models\Subscription;
use App\Support\SubscriptionCheckoutResult;

interface SubscriptionPaymentProvider
{
    public function code(): string;

    public function checkout(
        Subscription $subscription
    ): SubscriptionCheckoutResult;
}