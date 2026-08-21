<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Tenant;
use App\Support\TenantGlobalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantGlobalContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_global_values_are_valid(): void
    {
        $this->assertTrue(
            TenantGlobalSettings::isValid(
                'BR',
                'pt-BR',
                'America/Fortaleza',
                'BRL'
            )
        );

        $this->assertTrue(
            TenantGlobalSettings::isValid(
                'JP',
                'ja',
                'Asia/Tokyo',
                'JPY'
            )
        );

        $this->assertTrue(
            TenantGlobalSettings::isValid(
                'CN',
                'zh-CN',
                'Asia/Shanghai',
                'CNY'
            )
        );
    }

    public function test_invalid_global_values_are_rejected(): void
    {
        $this->assertFalse(
            TenantGlobalSettings::isValid(
                'ZZ',
                'invalid',
                'Invalid/Timezone',
                'XXX'
            )
        );
    }

    public function test_middleware_applies_tenant_locale_without_changing_runtime_timezone(): void
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

        $request = Request::create(
            'http://tenant-japan.localhost/context-test',
            'GET'
        );

        $capturedLocale = null;
        $capturedTimezone = null;
        $capturedTenantId = null;

        $middleware = app(ResolveTenant::class);

        $response = $middleware->handle(
            $request,
            function (Request $request) use (
                &$capturedLocale,
                &$capturedTimezone,
                &$capturedTenantId
            ) {
                $capturedLocale = app()->getLocale();
                $capturedTimezone = date_default_timezone_get();
                $capturedTenantId = $request->attributes
                    ->get('tenant')
                    ->id;

                return response('OK');
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ja', $capturedLocale);
        $this->assertSame('UTC', $capturedTimezone);
        $this->assertSame($tenant->id, $capturedTenantId);
    }

    public function test_middleware_restores_locale_and_preserves_runtime_timezone_after_request(): void
    {
        Tenant::create([
            'name' => 'Tenant Japan',
            'slug' => 'tenant-japan',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        $previousLocale = app()->getLocale();
        $previousTimezone = date_default_timezone_get();

        $request = Request::create(
            'http://tenant-japan.localhost/context-test',
            'GET'
        );

        app(ResolveTenant::class)->handle(
            $request,
            fn () => response('OK')
        );

        $this->assertSame(
            $previousLocale,
            app()->getLocale()
        );

        $this->assertSame(
            $previousTimezone,
            date_default_timezone_get()
        );
    }
}