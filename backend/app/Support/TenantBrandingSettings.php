<?php

namespace App\Support;

use InvalidArgumentException;

final class TenantBrandingSettings
{
    public const DEFAULT_PRIMARY_COLOR = '#2563EB';

    public static function normalizePrimaryColor(
        ?string $color
    ): ?string {
        if ($color === null) {
            return null;
        }

        $color = strtoupper(trim($color));

        if ($color === '') {
            return null;
        }

        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            throw new InvalidArgumentException(
                "Invalid tenant brand primary color: {$color}"
            );
        }

        return $color;
    }

    public static function normalizeLogoPath(
        ?string $path
    ): ?string {
        if ($path === null) {
            return null;
        }

        $path = trim($path);

        return $path === ''
            ? null
            : $path;
    }

    public static function primaryColor(
        ?string $color
    ): string {
        return self::normalizePrimaryColor($color)
            ?? self::DEFAULT_PRIMARY_COLOR;
    }
}