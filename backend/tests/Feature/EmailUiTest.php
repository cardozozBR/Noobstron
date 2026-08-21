<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantCapabilities;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_email_index_uses_tenant_locale(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-ui-locale',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.view'
        );

        $this->actingAs(
            $user
        );

        $this->get("http://{$tenant->slug}.localhost/email")
            ->assertOk()
            ->assertSee(
                'Mantenha suas conversas organizadas'
            );
    }

    public function test_create_form_uses_translations(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-ui-create',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.create'
        );

        $this->grantPermission(
            $user,
            'email.send'
        );

        $this->actingAs(
            $user
        );

        $this->get("http://{$tenant->slug}.localhost/email/create")
            ->assertOk()
            ->assertSee(
                'Nova mensagem'
            )
            ->assertSee(
                'E-mail do destinatário'
            )
            ->assertSee(
                'Enviar agora'
            );
    }

    public function test_ai_rewrite_is_available_with_feature_and_permission(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-ui-ai',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.create'
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::AI,
            true
        );

        $this->grantPermission(
            $user,
            'ai.use'
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email/create"
        )
            ->assertOk()
            ->assertSee(
                'Reescrever com IA'
            )
            ->assertSee(
                'id="email_ai_rewrite"',
                false
            )
            ->assertSee(
                route('ai.rewrite'),
                false
            );
    }

    public function test_ai_rewrite_is_hidden_without_ai_permission(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-ui-ai-denied',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.create'
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::AI,
            true
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email/create"
        )
            ->assertOk()
            ->assertDontSee(
                'id="email_ai_rewrite"',
                false
            );
    }
    public function test_template_ai_rewrite_is_available_with_feature_and_permission(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-template-ui-ai',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.templates'
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::AI,
            true
        );

        $this->grantPermission(
            $user,
            'ai.use'
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email/templates"
        )
            ->assertOk()
            ->assertSee(
                'Reescrever com IA'
            )
            ->assertSee(
                'id="email_template_ai_rewrite_new"',
                false
            )
            ->assertSee(
                route('ai.rewrite'),
                false
            );
    }

    public function test_template_ai_rewrite_is_hidden_without_ai_permission(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-template-ui-ai-denied',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.templates'
        );

        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::AI,
            true
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email/templates"
        )
            ->assertOk()
            ->assertDontSee(
                'id="email_template_ai_rewrite_new"',
                false
            );
    }
    public function test_templates_page_is_easy_to_understand(): void
    {
        [
            $tenant,
            $user,
        ] = $this->environment(
            'email-ui-template',
            'pt-BR'
        );

        $this->allow(
            $tenant,
            $user,
            'email.templates'
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/email/templates"
        )
            ->assertOk()
            ->assertSee(
                'Templates de e-mail'
            )
            ->assertSee(
                'Variáveis disponíveis'
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
                'Email UI User',

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

    private function grantPermission(
        User $user,
        string $permission
    ): void {
        $id = DB::table(
            'permissions'
        )
            ->where(
                'name',
                $permission
            )
            ->value(
                'id'
            );

        if ($id === null) {
            throw new \RuntimeException(
                'Permission not found: '
                . $permission
            );
        }

        $user->permissions()
            ->syncWithoutDetaching([
                $id,
            ]);
    }
    private function allow(
        Tenant $tenant,
        User $user,
        string $permission
    ): void {
        app(
            TenantCapabilities::class
        )->set(
            $tenant,
            \App\Enums\Feature::EMAIL,
            true
        );

        $id = DB::table(
            'permissions'
        )
            ->where(
                'name',
                $permission
            )
            ->value(
                'id'
            );

        $user->permissions()
            ->syncWithoutDetaching([
                $id,
            ]);
    }
}