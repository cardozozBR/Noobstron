<?php

namespace App\Contracts;

use App\Support\WhatsAppWebhookEvent;
use Illuminate\Http\Request;

interface WhatsAppWebhookNormalizer
{
    /**
     * @return array<int, WhatsAppWebhookEvent>
     */
    public function normalize(
        Request $request
    ): array;
}