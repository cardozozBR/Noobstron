<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $slug = 'tenant-a'): Tenant
    {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function user(Tenant $tenant): User
    {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usuário Teste',
            'email' => 'usuario@tenant-a.local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    public function test_not_found_uses_custom_404_page(): void
    {
        $this->tenant();

        $response = $this->get(
            'http://tenant-a.localhost/rota-inexistente'
        );

        $response->assertNotFound();
        $response->assertSee('Página não encontrada');
    }

    public function test_forbidden_uses_custom_403_page(): void
    {
        $tenant = $this->tenant();
        $user = $this->user($tenant);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-a.localhost/audit');

        $response->assertForbidden();
        $response->assertSee('Acesso negado');
    }

    public function test_feature_unavailable_uses_specific_403_page(): void
    {
        $tenant = $this->tenant(
            'feature-unavailable'
        );

        $user = $this->user(
            $tenant
        );

        Route::middleware('web')
            ->get(
                '/teste-feature-indisponivel',
                function (): void {
                    throw new \App\Exceptions\FeatureUnavailableException(
                        'catalog'
                    );
                }
            );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://feature-unavailable.localhost/teste-feature-indisponivel'
            );

        $response
            ->assertForbidden()
            ->assertSee(
                'Recurso não disponível no seu plano'
            )
            ->assertSee(
                'Este recurso não está incluído no plano atual da sua organização.'
            );
    }

    public function test_regular_forbidden_does_not_show_plan_message(): void
    {
        $tenant = $this->tenant(
            'regular-forbidden'
        );

        $user = $this->user(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://regular-forbidden.localhost/audit'
            );

        $response
            ->assertForbidden()
            ->assertSee(
                'Acesso negado'
            )
            ->assertDontSee(
                'Recurso não disponível no seu plano'
            );
    }
    public function test_419_uses_custom_page(): void
    {
        $this->tenant();

        Route::middleware('web')->get('/teste-erro-419', function () {
            abort(419);
        });

        $response = $this->get(
            'http://tenant-a.localhost/teste-erro-419'
        );

        $response->assertStatus(419);
        $response->assertSee('Sessão expirada');
    }

    public function test_unexpected_exception_uses_custom_500_page(): void
    {
        $this->tenant();

        config(['app.debug' => false]);

        Route::middleware('web')->get('/teste-erro-500', function () {
            throw new RuntimeException('Erro proposital de teste.');
        });

        $response = $this->get(
            'http://tenant-a.localhost/teste-erro-500'
        );

        $response->assertStatus(500);
        $response->assertSee('Erro interno');
        $response->assertDontSee('Erro proposital de teste.');
    }

    public function test_usage_limit_exceeded_suggests_upgrade(): void
    {
        $exception = \App\Exceptions\UsageBlockedException::exceeded(
            metric: \App\Enums\UsageMetric::MESSAGES,
            used: 100,
            requested: 1,
            limit: 100,
            remaining: 0,
            plan: new \App\Models\Plan([
                'name' => 'Plano atual',
            ]),
        );

                \Illuminate\Support\Facades\Route::get(
            '/__test_usage_upgrade',
            static function () use ($exception): never {
                throw $exception;
            }
        );

        $response = $this
            ->withExceptionHandling()
            ->get('/__test_usage_upgrade');

        $response->assertStatus(429);
        $response->assertSee(
            __('errors.usage_limit.title')
        );
        $response->assertSee(
            __('errors.usage_limit.upgrade_suggestion')
        );
    }
}