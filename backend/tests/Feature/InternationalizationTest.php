<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class InternationalizationTest extends TestCase
{
    use RefreshDatabase;
    public function test_all_supported_locales_have_ui_translations(): void
    {
        foreach (array_keys(config('global.locales')) as $locale) {
            App::setLocale($locale);

            $this->assertNotSame(
                'ui.app_name',
                __('ui.app_name'),
                "Missing ui.app_name translation for locale {$locale}."
            );

            $this->assertNotSame(
                'ui.navigation.logout',
                __('ui.navigation.logout'),
                "Missing ui.navigation.logout translation for locale {$locale}."
            );
        }
    }

    public function test_supported_locales_have_expected_translation_files(): void
    {
        foreach (array_keys(config('global.locales')) as $locale) {
            $this->assertFileExists(
                lang_path("{$locale}/ui.php"),
                "Missing UI translation file for locale {$locale}."
            );
        }
    }

    public function test_fallback_locale_is_supported(): void
    {
        $this->assertContains(
            config('app.fallback_locale'),
            array_keys(config('global.locales'))
        );
    }
    public function test_login_view_uses_tenant_locale(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Tenant Japan',
            'slug' => 'tenant-japan',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        $response = $this->get(
            'http://tenant-japan.localhost/login'
        );

        $response->assertOk();
        $response->assertSee('lang="ja"', false);
        $response->assertSee('ログイン', false);
        $response->assertSee('メールアドレス', false);
        $response->assertSee('パスワード', false);
    }
    public function test_dashboard_uses_tenant_locale(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Tenant Japan Dashboard',
            'slug' => 'tenant-japan-dashboard',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        app(\App\Services\TenantContext::class)->set($tenant);

        $user = \App\Models\User::create([
            'name' => 'Japanese Admin',
            'email' => 'admin-japan@example.test',
            'password' => 'TesteSenha123',
            'role' => 'admin',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-japan-dashboard.localhost/dashboard');

        $response->assertOk();
        $response->assertSee('lang="ja"', false);
        $response->assertSee('ダッシュボード', false);
        $response->assertSee('テナント概要', false);
        $response->assertSee('管理', false);
    }
    public function test_validation_messages_use_tenant_locale(): void
    {
        \App\Models\Tenant::create([
            'name' => 'Tenant Japan Validation',
            'slug' => 'tenant-japan-validation',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        $response = $this->post(
            'http://tenant-japan-validation.localhost/login',
            [
                'email' => '',
                'password' => '',
            ]
        );

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスは必須です。',
            'password' => 'パスワードは必須です。',
        ]);
    }

    public function test_custom_auth_validation_uses_tenant_locale(): void
    {
        \App\Models\Tenant::create([
            'name' => 'Tenant Spain Validation',
            'slug' => 'tenant-spain-validation',
            'status' => 'active',
            'country_code' => 'ES',
            'locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'currency' => 'EUR',
        ]);

        $response = $this->post(
            'http://tenant-spain-validation.localhost/login',
            [
                'email' => 'nao-existe@example.test',
                'password' => 'SenhaErrada123',
            ]
        );

        $response->assertSessionHasErrors([
            'email' => 'El correo electrónico o la contraseña no son válidos.',
        ]);
    }
}
