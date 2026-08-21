<?php

namespace App\Enums;

enum ImportTarget: string
{
    case LEADS = 'leads';
    case CUSTOMERS = 'customers';

    public function requiredFields(): array
    {
        return match ($this) {
            self::LEADS => [
                'name',
            ],

            self::CUSTOMERS => [
                'name',
                'type',
            ],
        };
    }

    public function supportedFields(): array
    {
        return match ($this) {
            self::LEADS => [
                'name',
                'email',
                'phone',
                'status',
                'source',
                'tags',
                'notes',
            ],

            self::CUSTOMERS => [
                'type',
                'name',
                'legal_name',
                'tax_country_code',
                'tax_identifier_type',
                'tax_identifier',
                'email',
                'phone',
                'tags',
                'notes',
            ],
        };
    }
}
