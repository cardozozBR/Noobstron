<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TenantGlobalSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_receives_safe_brazilian_defaults(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Brasil',
            'slug' => 'tenant-brasil',
            'status' => 'active',
        ]);

        $tenant->refresh();

        $this->assertSame('BR', $tenant->country_code);
        $this->assertSame('pt-BR', $tenant->locale);
        $this->assertSame('America/Fortaleza', $tenant->timezone);
        $this->assertSame('BRL', $tenant->currency);
    }

    public function test_tenant_can_store_international_settings(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Japan',
            'slug' => 'tenant-japan',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        $tenant->refresh();

        $this->assertSame('JP', $tenant->country_code);
        $this->assertSame('ja', $tenant->locale);
        $this->assertSame('Asia/Tokyo', $tenant->timezone);
        $this->assertSame('JPY', $tenant->currency);
    }

    public function test_global_settings_remain_independent_between_tenants(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ]);

        $this->assertSame('BRL', $tenantA->currency);
        $this->assertSame('USD', $tenantB->currency);
        $this->assertSame('pt-BR', $tenantA->locale);
        $this->assertSame('en', $tenantB->locale);
        $this->assertNotSame(
            $tenantA->timezone,
            $tenantB->timezone
        );
    }

    public function test_country_and_currency_are_normalized(): void
    {
        $tenant = Tenant::create([
            'name' => 'Normalized Tenant',
            'slug' => 'normalized-tenant',
            'status' => 'active',
            'country_code' => ' jp ',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => ' jpy ',
        ]);

        $this->assertSame('JP', $tenant->country_code);
        $this->assertSame('JPY', $tenant->currency);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Tenant::create([
            'name' => 'Invalid Locale',
            'slug' => 'invalid-locale',
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'xx-INVALID',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    public function test_unsupported_country_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Tenant::create([
            'name' => 'Invalid Country',
            'slug' => 'invalid-country',
            'status' => 'active',
            'country_code' => 'ZZ',
            'locale' => 'en',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ]);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Tenant::create([
            'name' => 'Invalid Timezone',
            'slug' => 'invalid-timezone',
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => 'Planet/Mars',
            'currency' => 'USD',
        ]);
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Tenant::create([
            'name' => 'Invalid Currency',
            'slug' => 'invalid-currency',
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'currency' => 'XXX',
        ]);
    }
    public function test_country_catalog_matches_global_configuration(): void
    {
        $configured = array_keys(
            config('global.countries')
        );

        sort($configured);

        $supported = [];

        foreach ($configured as $code) {
            $supported[] = \App\Support\Country::from($code)->code();
        }

        sort($supported);

        $this->assertSame(
            $configured,
            $supported
        );
    }
}