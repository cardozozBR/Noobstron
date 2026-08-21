<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentWebhookVerifier
{
    public function verify(
        Request $request
    ): bool;
}