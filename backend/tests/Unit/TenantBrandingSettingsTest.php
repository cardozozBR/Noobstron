<?php

namespace Tests\Unit;

use App\Support\TenantBrandingSettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TenantBrandingSettingsTest extends TestCase
{
    public function test_primary_color_is_normalized(): void
    {
        $this->assertSame(
            '#2563EB',
            TenantBrandingSettings::normalizePrimaryColor(
                '  #2563eb  '
            )
        );
    }

    public function test_empty_primary_color_becomes_null(): void
    {
        $this->assertNull(
            TenantBrandingSettings::normalizePrimaryColor('')
        );

        $this->assertNull(
            TenantBrandingSettings::normalizePrimaryColor('   ')
        );
    }

    public function test_invalid_primary_color_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TenantBrandingSettings::normalizePrimaryColor(
            'blue'
        );
    }

    public function test_short_hex_color_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        TenantBrandingSettings::normalizePrimaryColor(
            '#FFF'
        );
    }

    public function test_default_primary_color_is_safe(): void
    {
        $this->assertSame(
            '#2563EB',
            TenantBrandingSettings::primaryColor(null)
        );
    }

    public function test_logo_path_is_trimmed(): void
    {
        $this->assertSame(
            'tenant-assets/logo.svg',
            TenantBrandingSettings::normalizeLogoPath(
                '  tenant-assets/logo.svg  '
            )
        );
    }

    public function test_empty_logo_path_becomes_null(): void
    {
        $this->assertNull(
            TenantBrandingSettings::normalizeLogoPath('')
        );
    }
}