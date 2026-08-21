<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface WhatsAppWebhookVerifier
{
    public function verify(
        Request $request
    ): bool;
}