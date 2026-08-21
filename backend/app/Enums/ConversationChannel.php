<?php

namespace App\Enums;

enum ConversationChannel: string
{
    case EMAIL = 'email';

    case WHATSAPP = 'whatsapp';
}