<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiPermissionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_ai_use_permission_exists(): void
    {
        $this->assertSame(
            'ai.use',
            PermissionEnum::AI_USE->value
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'name' => 'ai.use',
            ]
        );
    }

    public function test_admin_receives_ai_use_permission(): void
    {
        $tenant = $this->tenant(
            'ai-permission-admin'
        );

        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'AI Admin',
            'email' => 'ai-admin@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => Role::ADMIN,
        ]);

        app(RolePermissionSync::class)->sync(
            $admin
        );

        $this->assertTrue(
            $admin
                ->permissions()
                ->where(
                    'name',
                    PermissionEnum::AI_USE->value
                )
                ->exists()
        );
    }

    public function test_regular_user_does_not_receive_ai_use_by_default(): void
    {
        $tenant = $this->tenant(
            'ai-permission-user'
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'AI User',
            'email' => 'ai-user@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => Role::USER,
        ]);

        app(RolePermissionSync::class)->sync(
            $user
        );

        $this->assertFalse(
            $user
                ->permissions()
                ->where(
                    'name',
                    PermissionEnum::AI_USE->value
                )
                ->exists()
        );
    }

    public function test_ai_use_can_be_granted_to_regular_user(): void
    {
        $tenant = $this->tenant(
            'ai-permission-grant'
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'AI Granted User',
            'email' => 'ai-granted@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => Role::USER,
        ]);

        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::AI_USE->value
            )
            ->firstOrFail();

        $user->permissions()
            ->attach(
                $permission->id
            );

        $this->assertTrue(
            $user
                ->permissions()
                ->where(
                    'name',
                    PermissionEnum::AI_USE->value
                )
                ->exists()
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}