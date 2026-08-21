<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InboxGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_feature_exists(): void
    {
        $this->assertSame(
            'inbox',
            Feature::INBOX->value
        );

        $this->assertSame(
            'Caixa de entrada',
            Feature::INBOX->label()
        );
    }

    public function test_inbox_permissions_exist(): void
    {
        $this->seed(
            PermissionSeeder::class
        );

        foreach ([
            PermissionEnum::INBOX_VIEW,
            PermissionEnum::INBOX_ASSIGN,
            PermissionEnum::INBOX_MANAGE,
        ] as $permission) {
            $this->assertDatabaseHas(
                'permissions',
                [
                    'name' =>
                        $permission->value,
                ]
            );
        }
    }

    public function test_admin_receives_inbox_permissions(): void
    {
        $this->seed(
            PermissionSeeder::class
        );

        $tenant = $this->tenant(
            'inbox-governance'
        );

        $admin = $this->user(
            $tenant,
            'admin',
            'admin'
        );

        app(
            RolePermissionSync::class
        )->sync(
            $admin
        );

        foreach ([
            PermissionEnum::INBOX_VIEW,
            PermissionEnum::INBOX_ASSIGN,
            PermissionEnum::INBOX_MANAGE,
        ] as $permission) {
            $this->assertTrue(
                $admin
                    ->permissions()
                    ->where(
                        'name',
                        $permission->value
                    )
                    ->exists()
            );
        }
    }

    public function test_permission_catalog_has_no_duplicates(): void
    {
        $values = array_map(
            fn (
                PermissionEnum $permission
            ): string =>
                $permission->value,
            PermissionEnum::cases()
        );

        $this->assertCount(
            count($values),
            array_unique($values)
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
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

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $suffix,
        string $role
    ): User {
        return User::query()
            ->create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    'Inbox Admin',

                'email' =>
                    "inbox-$suffix@example.com",

                'password' =>
                    Hash::make(
                        'TesteSenha123'
                    ),

                'role' =>
                    $role,
            ]);
    }
}