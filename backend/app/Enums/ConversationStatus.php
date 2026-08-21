<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case OPEN = 'open';

    case PENDING = 'pending';

    case CLOSED = 'closed';
}