<?php

namespace App\Enums;

enum CustomerType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL =>
                __('customers.individual'),

            self::COMPANY =>
                __('customers.company'),
        };
    }
}
