<?php

namespace App\Contracts;

use App\Models\WhatsAppMessage;
use App\Support\WhatsAppProviderResult;

interface WhatsAppProvider
{
    public function name(): string;

    public function send(
        WhatsAppMessage $message
    ): WhatsAppProviderResult;
}