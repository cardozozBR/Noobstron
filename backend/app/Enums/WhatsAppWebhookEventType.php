<?php

namespace App\Enums;

enum WhatsAppWebhookEventType: string
{
    case RECEIVED = 'received';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';
}