<?php

namespace App\Contracts;

use App\Models\Charge;
use App\Support\PaymentProviderResult;

interface PaymentProvider
{
    public function checkout(
        Charge $charge
    ): PaymentProviderResult;

    public function refund(
        Charge $charge
    ): PaymentProviderResult;
}