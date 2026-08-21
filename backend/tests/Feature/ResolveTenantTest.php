<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveTenantTest extends TestCase
{
    use RefreshDatabase;

   public function test_active_tenant_is_resolved_from_subdomain(): void
{
    $tenant = Tenant::create([
        'name' => 'Tenant Teste',
        'slug' => 'tenant-teste',
        'status' => 'active',
    ]);

    $request = \Illuminate\Http\Request::create(
        '/login',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_HOST' => 'tenant-teste.localhost',
        ]
    );

    $middleware = app(\App\Http\Middleware\ResolveTenant::class);

    $response = $middleware->handle(
        $request,
        function ($request) {
            return response()->json([
                'tenant_id' => app(TenantContext::class)->id(),
            ]);
        }
    );

    $this->assertSame(200, $response->getStatusCode());

    $this->assertSame(
        $tenant->id,
        app(TenantContext::class)->id()
    );
}
    public function test_inactive_tenant_returns_not_found(): void
    {
        Tenant::create([
            'name' => 'Tenant Inativo',
            'slug' => 'tenant-inativo',
            'status' => 'inactive',
        ]);

        $response = $this->withServerVariables([
            'HTTP_HOST' => 'tenant-inativo.localhost',
        ])->get('/login');

        $response->assertNotFound();
    }

    public function test_unknown_tenant_returns_not_found(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => 'nao-existe.localhost',
        ])->get('/login');

        $response->assertNotFound();
    }
}