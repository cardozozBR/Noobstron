<?php

namespace App\Enums;

enum CommercialContactStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case CONVERTED = 'converted';
    case LOST = 'lost';

    public function label(): string
    {
        return __('platform.contacts.statuses.'.$this->value);
    }
}