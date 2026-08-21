<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_email_feature_exists(): void
    {
        $this->assertSame(
            'email',
            Feature::EMAIL->value
        );

        $this->assertSame(
            'E-mail',
            Feature::EMAIL->label()
        );
    }

    public function test_email_permissions_exist(): void
    {
        foreach (
            [
                PermissionEnum::EMAIL_VIEW,
                PermissionEnum::EMAIL_CREATE,
                PermissionEnum::EMAIL_SEND,
                PermissionEnum::EMAIL_TEMPLATES,
            ] as $permission
        ) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' =>
                        $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_email_permissions(): void
    {
        $tenant = $this->tenant(
            'email-governance-admin'
        );

        $admin = User::query()->create([
            'tenant_id' =>
                $tenant->id,

            'name' =>
                'Email Admin',

            'email' =>
                'email-admin@local',

            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),

            'role' =>
                'admin',
        ]);

        app(RolePermissionSync::class)
            ->sync(
                $admin
            );

        foreach (
            [
                'email.view',
                'email.create',
                'email.send',
                'email.templates',
            ] as $permission
        ) {
            $this->assertTrue(
                $admin
                    ->permissions()
                    ->where(
                        'name',
                        $permission
                    )
                    ->exists()
            );
        }
    }

    public function test_email_feature_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'email-governance-a'
        );

        $tenantB = $this->tenant(
            'email-governance-b'
        );

        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenantA,
            Feature::EMAIL,
            true
        );

        $capabilities->set(
            $tenantB,
            Feature::EMAIL,
            false
        );

        $this->assertTrue(
            $capabilities->enabled(
                $tenantA,
                Feature::EMAIL
            )
        );

        $this->assertFalse(
            $capabilities->enabled(
                $tenantB,
                Feature::EMAIL
            )
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
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
                'pt-BR',

            'timezone' =>
                'America/Fortaleza',

            'currency' =>
                'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}