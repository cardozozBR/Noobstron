<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
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

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::ACTIVITIES,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Activity UI User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $model->id
            );
    }

    public function test_ai_rewrite_is_available_with_feature_and_permission(): void
    {
        $tenant = $this->tenant(
            'activity-ui-ai'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AI,
            true
        );

        $user = $this->user(
            $tenant,
            'activity-ai@local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_CREATE
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/activities/create"
            )
            ->assertOk()
            ->assertSee(
                'Reescrever com IA'
            )
            ->assertSee(
                'id="activity_ai_rewrite"',
                false
            );
    }

    public function test_ai_rewrite_is_hidden_without_ai_permission(): void
    {
        $tenant = $this->tenant(
            'activity-ui-no-ai'
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AI,
            true
        );

        $user = $this->user(
            $tenant,
            'activity-no-ai@local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_CREATE
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/activities/create"
            )
            ->assertOk()
            ->assertDontSee(
                'id="activity_ai_rewrite"',
                false
            );
    }
}