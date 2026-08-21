<?php

namespace App\Support;

use InvalidArgumentException;

class TenantGlobalSettings
{
    public static function locales(): array
    {
        return array_keys(config('global.locales', []));
    }

    public static function countries(): array
    {
        return array_keys(config('global.countries', []));
    }

    public static function currencies(): array
    {
        return array_keys(config('global.currencies', []));
    }

    public static function defaults(): array
    {
        return config('global.defaults', []);
    }

    public static function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::locales(), true);
    }

    public static function isValidCountry(string $country): bool
    {
        return in_array($country, self::countries(), true);
    }

    public static function isValidCurrency(string $currency): bool
    {
        return in_array($currency, self::currencies(), true);
    }

    public static function isValidTimezone(string $timezone): bool
    {
        return in_array(
            $timezone,
            timezone_identifiers_list(),
            true
        );
    }

    public static function isValid(
        string $country,
        string $locale,
        string $timezone,
        string $currency
    ): bool {
        return self::isValidCountry($country)
            && self::isValidLocale($locale)
            && self::isValidTimezone($timezone)
            && self::isValidCurrency($currency);
    }

    public static function assertValid(
        string $country,
        string $locale,
        string $timezone,
        string $currency
    ): void {
        if (!self::isValidCountry($country)) {
            throw new InvalidArgumentException(
                "Unsupported tenant country: {$country}"
            );
        }

        if (!self::isValidLocale($locale)) {
            throw new InvalidArgumentException(
                "Unsupported tenant locale: {$locale}"
            );
        }

        if (!self::isValidTimezone($timezone)) {
            throw new InvalidArgumentException(
                "Invalid tenant timezone: {$timezone}"
            );
        }

        if (!self::isValidCurrency($currency)) {
            throw new InvalidArgumentException(
                "Unsupported tenant currency: {$currency}"
            );
        }
    }
}