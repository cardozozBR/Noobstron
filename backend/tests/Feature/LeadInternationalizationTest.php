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

class LeadInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function tenant(
        string $slug,
        string $locale
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'locale' => $locale,
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Admin',
            'email' => 'lead-admin@'
                . $tenant->slug
                . '.local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grantView(
        User $user
    ): void {
        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::LEADS_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $permission->id
            );
    }

    public function test_all_supported_locales_have_lead_translations(): void
    {
        foreach ([
            'pt-BR',
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $this->assertFileExists(
                lang_path(
                    $locale . '/leads.php'
                )
            );
        }
    }

    public function test_japanese_leads_view_uses_tenant_locale(): void
    {
        $tenant = $this->tenant(
            'leads-ja',
            'ja'
        );

        $user = $this->user(
            $tenant
        );

        $this->grantView(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-ja.localhost/leads'
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ja"',
            false
        );

        $response->assertSee(
            'リード',
            false
        );

        $response->assertSee(
            '流入元',
            false
        );

        $response->assertSee(
            '担当者',
            false
        );
    }

    public function test_leads_navigation_is_visible_with_permission_and_feature(): void
    {
        $tenant = $this->tenant(
            'leads-nav',
            'pt-BR'
        );

        $user = $this->user(
            $tenant
        );

        $this->grantView(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-nav.localhost/leads'
            );

        $response->assertOk();

        $response->assertSee(
            route('leads.index'),
            false
        );
    }

    public function test_leads_navigation_is_hidden_without_permission(): void
    {
        $tenant = $this->tenant(
            'leads-nav-hidden',
            'pt-BR'
        );

        $user = $this->user(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-nav-hidden.localhost/profile'
            );

        $response->assertDontSee(
            route('leads.index'),
            false
        );
    }
}
