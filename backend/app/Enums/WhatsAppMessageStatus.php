<?php

namespace App\Enums;

enum WhatsAppMessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case RECEIVED = 'received';
    case FAILED = 'failed';
}