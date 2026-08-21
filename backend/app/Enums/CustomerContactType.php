<?php

namespace App\Enums;

enum CustomerContactType: string
{
    case GENERAL = 'general';
    case COMMERCIAL = 'commercial';
    case FINANCIAL = 'financial';
    case TECHNICAL = 'technical';
    case OTHER = 'other';
}
