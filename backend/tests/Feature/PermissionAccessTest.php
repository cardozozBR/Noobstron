<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Feature;
use App\Support\TenantCapabilities;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Tenant Teste',
            'slug' => 'tenant-teste',
            'status' => 'active',
        ]);
    }

    private function createUser(
        Tenant $tenant,
        string $email = 'usuario@tenant-teste.local',
    ): User {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usuário Teste',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
            'tenant_id' => $tenant->id,
        ]);
    }

    private function grantPermission(
        User $user,
        PermissionEnum $permission
    ): void {
        $permissionModel = Permission::where(
            'name',
            $permission->value
        )->firstOrFail();

        $user->permissions()->syncWithoutDetaching(
            $permissionModel->id
        );
    }


    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
{
    $tenant = $this->createTenant();

    $response = $this
        ->get("http://{$tenant->slug}.localhost/dashboard");

    $response->assertRedirect("http://{$tenant->slug}.localhost/login");
}

public function test_guest_is_redirected_to_login_when_accessing_profile(): void
{
    $tenant = $this->createTenant();

    $response = $this
        ->get("http://{$tenant->slug}.localhost/profile");

    $response->assertRedirect("http://{$tenant->slug}.localhost/login");
}

public function test_guest_is_redirected_to_login_when_accessing_users(): void
{
    $tenant = $this->createTenant();

    $response = $this
        ->get("http://{$tenant->slug}.localhost/users");

    $response->assertRedirect("http://{$tenant->slug}.localhost/login");
}

public function test_guest_is_redirected_to_login_when_accessing_audit(): void
{
    $tenant = $this->createTenant();

    $response = $this
        ->get("http://{$tenant->slug}.localhost/audit");

    $response->assertRedirect("http://{$tenant->slug}.localhost/login");
}



 public function test_resolve_tenant_identifies_tenant_from_host(): void
{
    $tenant = $this->createTenant();

    $request = \Illuminate\Http\Request::create(
        '/users',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_HOST' => 'tenant-teste.localhost',
            'SERVER_NAME' => 'tenant-teste.localhost',
        ]
    );


    app(\App\Http\Middleware\ResolveTenant::class)->handle(
        $request,
        fn ($request) => response()->noContent()
    );

    $this->assertSame(
        $tenant->id,
        app(TenantContext::class)->get()->id
    );
}
   public function test_user_with_users_view_can_access_users(): void
{
    $tenant = $this->createTenant();

    $user = $this->createUser($tenant);

    $this->grantPermission(
        $user,
        PermissionEnum::USERS_VIEW
    );
    app(TenantCapabilities::class)->set(
        $tenant,
        Feature::USERS,
        true
    );

    $response = $this
        ->actingAs($user)
        ->get('http://tenant-teste.localhost/users');

   

    $response->assertOk();
}
    public function test_user_without_users_view_gets_forbidden(): void
{
    $tenant = $this->createTenant();

    $user = $this->createUser($tenant);

    $response = $this
        ->actingAs($user)
        ->get('http://tenant-teste.localhost/users');

    $response->assertForbidden();
}

    public function test_user_with_audit_view_can_access_audit(): void
{
    $tenant = $this->createTenant();

    $user = $this->createUser(
        $tenant,
        'auditoria@tenant-teste.local'
    );

    $this->grantPermission(
        $user,
        PermissionEnum::AUDIT_VIEW
    );
    app(TenantCapabilities::class)->set(
        $tenant,
        Feature::AUDIT,
        true
    );

    $response = $this
        ->actingAs($user)
        ->get('http://tenant-teste.localhost/audit');

    $response->assertOk();
}

    public function test_user_without_audit_view_gets_forbidden(): void
{
    $tenant = $this->createTenant();

    $user = $this->createUser(
        $tenant,
        'sem-auditoria@tenant-teste.local'
    );

    $response = $this
        ->actingAs($user)
        ->get('http://tenant-teste.localhost/audit');

    $response->assertForbidden();
}

    public function test_user_with_audit_permission_is_blocked_when_audit_feature_is_disabled(): void
    {
        $tenant = $this->createTenant();

        $user = $this->createUser(
            $tenant,
            'auditoria-feature-off@tenant-teste.local'
        );

        $this->grantPermission(
            $user,
            PermissionEnum::AUDIT_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AUDIT,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://tenant-teste.localhost/audit'
            );

        $response->assertForbidden();
    }

    public function test_user_with_audit_permission_can_access_when_audit_feature_is_enabled(): void
    {
        $tenant = $this->createTenant();

        $user = $this->createUser(
            $tenant,
            'auditoria-feature-on@tenant-teste.local'
        );

        $this->grantPermission(
            $user,
            PermissionEnum::AUDIT_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AUDIT,
            true
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://tenant-teste.localhost/audit'
            );

        $response->assertOk();
    }
}