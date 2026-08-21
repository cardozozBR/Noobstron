<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InboxUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_inbox_translation_files_exist(): void
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
                    $locale . '/inbox.php'
                )
            );
        }
    }

    public function test_inbox_views_exist(): void
    {
        foreach ([
            resource_path(
                'views/inbox/index.blade.php'
            ),
            resource_path(
                'views/inbox/show.blade.php'
            ),
        ] as $view) {
            $this->assertFileExists(
                $view
            );
        }
    }

    public function test_inbox_index_uses_tenant_locale(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'inbox-ui-locale',
            'pt-BR'
        );

        $this->allowInbox(
            $tenant,
            $user
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/inbox"
        )
            ->assertOk()
            ->assertSee(
                'Caixa de entrada'
            )
            ->assertSee(
                'Acompanhe suas conversas'
            );
    }

    public function test_inbox_navigation_is_visible_when_allowed(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'inbox-ui-nav',
            'pt-BR'
        );

        $this->allowInbox(
            $tenant,
            $user
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/inbox"
        )
            ->assertOk()
            ->assertSee(
                route(
                    'inbox.index'
                ),
                false
            );
    }

    private function environment(
        string $slug,
        string $locale
    ): array {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,

            'slug' =>
                $slug,

            'status' =>
                'active',

            'country_code' =>
                'BR',

            'locale' =>
                $locale,

            'timezone' =>
                'America/Fortaleza',

            'currency' =>
                'BRL',
        ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' =>
                $tenant->id,

            'name' =>
                'Inbox UI User',

            'email' =>
                $slug . '@local',

            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),

            'role' =>
                'user',
        ]);

        return [
            $tenant,
            $user,
        ];
    }

    private function allowInbox(
        Tenant $tenant,
        User $user
    ): void {
        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            Feature::INBOX,
            true
        );

        $permissionId = DB::table(
            'permissions'
        )
            ->where(
                'name',
                'inbox.view'
            )
            ->value(
                'id'
            );

        if ($permissionId === null) {
            throw new \RuntimeException(
                'Permission not found: inbox.view'
            );
        }

        $user->permissions()
            ->syncWithoutDetaching([
                $permissionId,
            ]);
    }
}