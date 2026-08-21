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

class ProposalUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function tenant(string $slug): Tenant
    {
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

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $name
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $tenant->slug
                . '-'
                . str($name)->slug()
                . '@local',
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

    private function enableProposalAndAi(
        Tenant $tenant
    ): void {
        $capabilities = app(
            TenantCapabilities::class
        );

        $capabilities->set(
            $tenant,
            Feature::PROPOSALS,
            true
        );

        $capabilities->set(
            $tenant,
            Feature::AI,
            true
        );
    }

    public function test_ai_rewrite_is_available_with_feature_and_permission(): void
    {
        $tenant = $this->tenant(
            'proposal-ui-ai'
        );

        $user = $this->user(
            $tenant,
            'ai-user'
        );

        $this->enableProposalAndAi(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this->grant(
            $user,
            PermissionEnum::AI_USE
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals/create"
            )
            ->assertOk()
            ->assertSee(
                'Reescrever com IA'
            )
            ->assertSee(
                'id="proposal_ai_rewrite"',
                false
            )
            ->assertSee(
                route('ai.rewrite'),
                false
            );
    }

    public function test_ai_rewrite_is_hidden_without_ai_permission(): void
    {
        $tenant = $this->tenant(
            'proposal-ui-ai-denied'
        );

        $user = $this->user(
            $tenant,
            'denied-user'
        );

        $this->enableProposalAndAi(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals/create"
            )
            ->assertOk()
            ->assertDontSee(
                'id="proposal_ai_rewrite"',
                false
            );
    }
}
