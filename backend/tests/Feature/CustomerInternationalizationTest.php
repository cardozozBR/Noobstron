<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function tenant(
        string $slug,
        string $locale = 'pt-BR'
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => $locale,
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Customer I18n User',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where('name', $permission->value)
            ->firstOrFail();

        $user->permissions()->syncWithoutDetaching(
            $model->id
        );
    }

    public function test_all_supported_locales_have_customer_translations(): void
    {
        foreach ([
            'pt-BR',
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $this->assertFileExists(
                lang_path($locale . '/customers.php')
            );
        }
    }

    public function test_customer_translation_keys_have_parity(): void
    {
        $base = require lang_path(
            'pt-BR/customers.php'
        );

        $baseKeys = array_keys($base);

        sort($baseKeys);

        foreach ([
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $translations = require lang_path(
                $locale . '/customers.php'
            );

            $keys = array_keys($translations);

            sort($keys);

            $this->assertSame(
                $baseKeys,
                $keys,
                "Customer translation keys differ for {$locale}."
            );
        }
    }

    public function test_japanese_customer_index_uses_tenant_locale(): void
    {
        $tenant = $this->tenant(
            'customers-ja',
            'ja'
        );

        $user = $this->user(
            $tenant,
            'ja@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => '日本企業',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-ja.localhost/customers'
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ja"',
            false
        );

        $response->assertSee(
            '顧客',
            false
        );

        $response->assertSee(
            '法人',
            false
        );
    }

    public function test_customers_navigation_is_visible_with_permission_and_feature(): void
    {
        $tenant = $this->tenant(
            'customers-nav'
        );

        $user = $this->user(
            $tenant,
            'nav@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-nav.localhost/customers'
            );

        $response->assertOk();

        $response->assertSee(
            route('customers.index'),
            false
        );
    }

    public function test_customers_navigation_does_not_require_users_permission(): void
    {
        $tenant = $this->tenant(
            'customers-nav-independent'
        );

        $user = $this->user(
            $tenant,
            'nav-independent@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-nav-independent.localhost/customers'
            );

        $response->assertOk();

        $response->assertSee(
            route('customers.index'),
            false
        );

        $response->assertDontSee(
            route('users.index'),
            false
        );
    }

    public function test_customers_navigation_is_hidden_without_permission(): void
    {
        $tenant = $this->tenant(
            'customers-nav-hidden'
        );

        $user = $this->user(
            $tenant,
            'nav-hidden@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::PROFILE_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-nav-hidden.localhost/profile'
            );

        $response->assertOk();

        $response->assertDontSee(
            route('customers.index'),
            false
        );
    }

    public function test_customers_navigation_is_hidden_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant(
            'customers-nav-feature-off'
        );

        $user = $this->user(
            $tenant,
            'nav-feature-off@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        $this->grant(
            $user,
            PermissionEnum::PROFILE_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-nav-feature-off.localhost/profile'
            );

        $response->assertOk();

        $response->assertDontSee(
            route('customers.index'),
            false
        );
    }
}
