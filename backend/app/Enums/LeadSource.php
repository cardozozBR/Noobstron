<?php

namespace App\Enums;

enum LeadSource: string
{
    case MANUAL = 'manual';
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case SOCIAL = 'social';
    case OTHER = 'other';

    public function label(): string
    {
        return __('leads.source.' . $this->value);
    }
}
