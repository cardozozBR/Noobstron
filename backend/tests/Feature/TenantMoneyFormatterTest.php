<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantContext;
use App\Support\Money;
use App\Support\TenantMoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantMoneyFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatter_uses_current_tenant_locale(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Brasil',
            'slug' => 'tenant-brasil-money',
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        $formatter = app(TenantMoneyFormatter::class);

        $formatted = $formatter->format(
            Money::fromMinor(105050, 'BRL')
        );

        $this->assertStringContainsString(
            '1.050,50',
            $formatted
        );
    }

    public function test_formatter_uses_tenant_default_currency(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Japan',
            'slug' => 'tenant-japan-money',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        app(TenantContext::class)->set($tenant);

        $formatter = app(TenantMoneyFormatter::class);

        $formatted = $formatter->formatMinor(1050);

        $this->assertSame(
            'JPY',
            $formatter->tenantCurrency()
        );

        $this->assertStringContainsString(
            '1,050',
            $formatted
        );
    }

    public function test_same_minor_value_is_formatted_differently_between_tenants(): void
    {
        $br = Tenant::create([
            'name' => 'Tenant BR',
            'slug' => 'tenant-br-money',
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        $us = Tenant::create([
            'name' => 'Tenant US',
            'slug' => 'tenant-us-money',
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ]);

        $context = app(TenantContext::class);
        $formatter = app(TenantMoneyFormatter::class);

        $context->set($br);
        $brFormatted = $formatter->formatMinor(105050);

        $context->set($us);
        $usFormatted = $formatter->formatMinor(105050);

        $this->assertNotSame(
            $brFormatted,
            $usFormatted
        );

        $this->assertStringContainsString(
            '1.050,50',
            $brFormatted
        );

        $this->assertStringContainsString(
            '1,050.50',
            $usFormatted
        );
    }
    public function test_currency_catalog_matches_global_configuration(): void
    {
        $configured = array_keys(
            config('global.currencies')
        );

        sort($configured);

        $supported = \App\Support\Currency::supported();

        sort($supported);

        $this->assertSame(
            $configured,
            $supported
        );
    }
}