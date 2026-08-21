<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PipelineService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PipelineInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function environment(
        string $slug,
        string $locale = 'pt-BR'
    ): array {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => $locale,
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::PIPELINES,
            true
        );

        $user = User::create([
            'name' => 'Pipeline I18n',
            'email' => $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        foreach ([
            PermissionEnum::PIPELINES_VIEW,
            PermissionEnum::PIPELINES_CREATE,
            PermissionEnum::PIPELINES_UPDATE,
            PermissionEnum::PIPELINES_DELETE,
        ] as $permissionEnum) {
            $permission = Permission::query()
                ->where(
                    'name',
                    $permissionEnum->value
                )
                ->firstOrFail();

            $user->permissions()
                ->syncWithoutDetaching(
                    $permission->id
                );
        }

        return [
            $tenant,
            $user,
        ];
    }

    public function test_all_supported_locales_have_pipeline_translations(): void
    {
        foreach ([
            'pt-BR',
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $path = lang_path(
                $locale
                    . '/pipelines.php'
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
                'stages',
                $translations
            );

            $this->assertArrayHasKey(
                'reorder',
                $translations
            );
        }
    }

    public function test_pipeline_translation_keys_have_parity(): void
    {
        $reference = array_keys(
            require lang_path(
                'pt-BR/pipelines.php'
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
                    $locale
                        . '/pipelines.php'
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

    public function test_japanese_pipeline_index_uses_tenant_locale(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-ja',
                'ja'
            );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines"
            );

        $response->assertOk();

        $response->assertSee(
            'パイプライン',
            false
        );

        $response->assertSee(
            'lang="ja"',
            false
        );
    }

    public function test_navigation_is_visible_with_permission_and_feature(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-nav'
            );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines"
            );

        $response->assertOk();

        $response->assertSee(
            route('pipelines.index'),
            false
        );
    }

    public function test_navigation_is_hidden_without_view_permission(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-nav-hidden'
            );

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::PIPELINES_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->detach(
                $permission->id
            );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/profile"
            );

        $response->assertOk();

        $response->assertDontSee(
            route('pipelines.index'),
            false
        );
    }

    public function test_create_page_is_translated(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-create-i18n'
            );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines/create"
            )
            ->assertOk()
            ->assertSee(
                __('pipelines.new'),
                false
            );
    }

    public function test_edit_page_contains_stage_management(): void
    {
        [$tenant, $user] =
            $this->environment(
                'pipeline-edit-i18n'
            );

        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/pipelines/{$pipeline->id}/edit"
            );

        $response->assertOk();

        $response->assertSee(
            __('pipelines.stages'),
            false
        );

        $response->assertSee(
            route(
                'pipelines.stages.store',
                $pipeline->id
            ),
            false
        );
    }
}
