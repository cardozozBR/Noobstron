<?php

namespace App\Support;

use InvalidArgumentException;

final class Currency
{
    /**
     * Number of minor-unit decimal places used by each supported currency.
     *
     * ISO 4217:
     * BRL, USD, EUR and CNY use 2 decimal places.
     * JPY uses 0 decimal places.
     */
    private const MINOR_UNITS = [
        'BRL' => 2,
        'USD' => 2,
        'EUR' => 2,
        'JPY' => 0,
        'CNY' => 2,
    ];

    public static function supported(): array
    {
        return array_keys(self::MINOR_UNITS);
    }

    public static function isSupported(string $currency): bool
    {
        return array_key_exists(
            strtoupper(trim($currency)),
            self::MINOR_UNITS
        );
    }

    public static function normalize(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (!self::isSupported($currency)) {
            throw new InvalidArgumentException(
                "Unsupported currency: {$currency}"
            );
        }

        return $currency;
    }

    public static function minorUnit(string $currency): int
    {
        $currency = self::normalize($currency);

        return self::MINOR_UNITS[$currency];
    }

    public static function factor(string $currency): int
    {
        return 10 ** self::minorUnit($currency);
    }
}