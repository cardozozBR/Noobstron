<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_locales_and_fallback(): void
    {
        $cases = [
            ['en-US,en;q=0.9', 'en', 'Organize your customer relationships'],
            ['es-MX,es;q=0.9', 'es', null],
            ['zh-CN,zh;q=0.9', 'zh-CN', null],
            ['ja-JP,ja;q=0.9', 'ja', null],
            ['fr-FR,fr;q=0.9', 'pt-BR', null],
        ];

        foreach ($cases as [$header, $locale, $text]) {
            $response = $this
                ->withHeader('Accept-Language', $header)
                ->get('http://localhost/');

            $response
                ->assertOk()
                ->assertSee('<html lang="' . $locale . '">', false);

            if ($text !== null) {
                $response->assertSee($text);
            }
        }
    }

    public function test_manual_locale_cookie_has_priority(): void
    {
        $this->get('http://localhost/idioma?locale=en&return=/entrar')
            ->assertRedirect('/entrar')
            ->assertCookie('public_locale', 'en');

        $this->withCookie('public_locale', 'en')
            ->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('<html lang="en">', false);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->get('http://localhost/idioma?locale=fr')
            ->assertNotFound();
    }

    public function test_public_selector_and_workspace_are_translated(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('name="locale"', false);

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('http://localhost/entrar')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Sign in to your account');
    }

    public function test_tenant_locale_still_wins_on_tenant_routes(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'English Tenant',
            'slug' => 'english-tenant',
            'status' => 'active',
            'country_code' => 'US',
            'locale' => 'en',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ]);

        $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('http://' . $tenant->slug . '.localhost/login')
            ->assertOk()
            ->assertSee('<html lang="en">', false);
    }
}