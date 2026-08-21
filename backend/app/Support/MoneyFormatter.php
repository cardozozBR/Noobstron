<?php

namespace App\Support;

use NumberFormatter;
use RuntimeException;

final class MoneyFormatter
{
    public static function format(
        Money $money,
        string $locale
    ): string {
        if (!class_exists(NumberFormatter::class)) {
            throw new RuntimeException(
                'PHP intl extension is required.'
            );
        }

        $formatter = new NumberFormatter(
            self::normalizeLocale($locale),
            NumberFormatter::CURRENCY
        );

        $value = $money->minor / $money->factor();

        $formatted = $formatter->formatCurrency(
            $value,
            $money->currency
        );

        if ($formatted === false) {
            throw new RuntimeException(
                'Unable to format monetary value.'
            );
        }

        return $formatted;
    }

    private static function normalizeLocale(
        string $locale
    ): string {
        return str_replace('-', '_', trim($locale));
    }
}