<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_all_supported_locales_have_import_translations(): void
    {
        foreach ([
            'pt-BR',
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $path = lang_path(
                $locale . '/imports.php'
            );

            $this->assertFileExists(
                $path
            );

            $translations = require $path;

            $this->assertArrayHasKey(
                'title',
                $translations
            );

            $this->assertArrayHasKey(
                'upload',
                $translations
            );

            $this->assertArrayHasKey(
                'preview',
                $translations
            );
        }
    }

    public function test_import_translation_keys_have_parity(): void
    {
        $reference = array_keys(
            require lang_path(
                'pt-BR/imports.php'
            )
        );

        sort($reference);

        foreach ([
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $keys = array_keys(
                require lang_path(
                    $locale . '/imports.php'
                )
            );

            sort($keys);

            $this->assertSame(
                $reference,
                $keys,
                $locale
            );
        }
    }

    public function test_japanese_import_page_uses_tenant_locale(): void
    {
        $tenant = Tenant::create([
            'name' => 'Imports JA',
            'slug' => 'imports-ja',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::IMPORTS,
            true
        );

        $user = User::create([
            'name' => 'Imports JA',
            'email' => 'imports-ja@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::IMPORTS_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->attach(
                $permission->id
            );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://imports-ja.localhost/imports'
            );

        $response->assertOk();

        $response->assertSee(
            'インポート',
            false
        );

        $response->assertSee(
            'lang="ja"',
            false
        );
    }

    public function test_import_navigation_is_visible_with_permission_and_feature(): void
    {
        $tenant = Tenant::create([
            'name' => 'Imports Nav',
            'slug' => 'imports-nav',
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::IMPORTS,
            true
        );

        $user = User::create([
            'name' => 'Imports Nav',
            'email' => 'imports-nav@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::IMPORTS_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->attach(
                $permission->id
            );

        $this
            ->actingAs($user)
            ->get(
                'http://imports-nav.localhost/imports'
            )
            ->assertOk()
            ->assertSee(
                route('imports.index'),
                false
            );
    }
}
