<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceLoginGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug,
        string $status = 'active'
    ): Tenant {
        return Tenant::query()->create([
            'name' => 'Empresa ' . $slug,
            'slug' => $slug,
            'status' => $status,
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Sao_Paulo',
            'currency' => 'BRL',
        ]);
    }

    public function test_public_workspace_gateway_is_available(): void
    {
        $this->get('http://localhost/entrar')
            ->assertOk()
            ->assertSee('Entrar na sua conta')
            ->assertSee('Workspace')
            ->assertSee('identificador da sua empresa')
            ->assertSee('name="workspace"', false);
    }

    public function test_home_enter_button_uses_workspace_gateway(): void
    {
        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee(
                'href="' . route('workspace.login') . '"',
                false
            );
    }

    public function test_marketing_header_enter_button_uses_workspace_gateway(): void
    {
        $response = $this->get(
            'http://localhost/'
        );

        $response->assertOk();

        $html = $response->getContent();

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $html,
                route('workspace.login')
            )
        );

        $this->assertStringNotContainsString(
            'href="' . route('login') . '"',
            $html
        );
    }
    public function test_active_workspace_redirects_to_tenant_login(): void
    {
        config([
            'app.url' => 'http://localhost',
        ]);

        $this->tenant('tenant-a');

        $this->post(
            'http://localhost:8000/entrar',
            [
                'workspace' => 'tenant-a',
            ]
        )->assertRedirect(
            'http://tenant-a.localhost:8000/login'
        );
    }

    public function test_unknown_workspace_is_rejected(): void
    {
        $this
            ->from('http://localhost/entrar')
            ->post(
                'http://localhost/entrar',
                [
                    'workspace' => 'nao-existe',
                ]
            )
            ->assertRedirect('http://localhost/entrar')
            ->assertSessionHasErrors('workspace');
    }

    public function test_inactive_workspace_is_rejected(): void
    {
        $this->tenant(
            'tenant-bloqueado',
            'blocked'
        );

        $this
            ->from('http://localhost/entrar')
            ->post(
                'http://localhost/entrar',
                [
                    'workspace' => 'tenant-bloqueado',
                ]
            )
            ->assertRedirect('http://localhost/entrar')
            ->assertSessionHasErrors('workspace');
    }

    public function test_original_tenant_login_still_works(): void
    {
        $tenant = $this->tenant(
            'tenant-login'
        );

        $this->get(
            'http://'
            . $tenant->slug
            . '.localhost/login'
        )->assertOk();
    }
}