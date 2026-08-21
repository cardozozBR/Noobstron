<?php

namespace App\Contracts;

use App\Support\PaymentProviderEvent;
use Illuminate\Http\Request;

interface PaymentWebhookNormalizer
{
    public function normalize(
        Request $request
    ): PaymentProviderEvent;
}