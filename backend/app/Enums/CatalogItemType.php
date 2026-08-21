<?php

namespace App\Enums;

enum CatalogItemType: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Produto',
            self::SERVICE => 'ServiÃ§o',
        };
    }
}
