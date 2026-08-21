<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Activity;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
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
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Activity User',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where('name', $permission->value)
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching($model->id);
    }

    private function activity(
        Tenant $tenant,
        string $title = 'Atividade Teste'
    ): Activity {
        app(TenantContext::class)->set($tenant);

        return app(ActivityService::class)->create([
            'type' => ActivityType::TASK,
            'status' => ActivityStatus::PENDING,
            'title' => $title,
        ]);
    }

    public function test_activity_routes_require_authentication(): void
    {
        $tenant = $this->tenant(
            'activity-http-auth'
        );

        $this->get(
            "http://{$tenant->slug}.localhost/activities"
        )->assertRedirect('/login');
    }

    public function test_index_requires_activity_feature(): void
    {
        $tenant = $this->tenant(
            'activity-http-feature'
        );

        $user = $this->user(
            $tenant,
            'feature@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::ACTIVITIES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/activities"
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        $tenant = $this->tenant(
            'activity-http-no-view'
        );

        $user = $this->user(
            $tenant,
            'no-view@activities.local'
        );

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/activities"
            )
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_index(): void
    {
        $tenant = $this->tenant(
            'activity-http-index'
        );

        $user = $this->user(
            $tenant,
            'index@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/activities"
            );

        $response->assertOk();

        $response->assertSee(
            __('activities.title')
        );

        $response->assertSee(
            __('activities.index_description')
        );
    }

    public function test_store_creates_activity_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'activity-http-store'
        );

        $user = $this->user(
            $tenant,
            'store@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/activities",
                [
                    'type' => ActivityType::TASK->value,
                    'title' => 'Nova atividade',
                ]
            );

        app(TenantContext::class)->set($tenant);

        $activity = Activity::query()
            ->where('title', 'Nova atividade')
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $activity->tenant_id
        );

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );

        $response->assertRedirect(
            route(
                'activities.edit',
                $activity->id
            )
        );
    }

    public function test_activity_from_other_tenant_cannot_be_edited(): void
    {
        $tenantA = $this->tenant(
            'activity-http-edit-a'
        );

        $activity = $this->activity(
            $tenantA,
            'Tenant A'
        );

        $tenantB = $this->tenant(
            'activity-http-edit-b'
        );

        $userB = $this->user(
            $tenantB,
            'edit-b@activities.local'
        );

        $this->grant(
            $userB,
            PermissionEnum::ACTIVITIES_UPDATE
        );

        $this
            ->actingAs($userB)
            ->get(
                "http://{$tenantB->slug}.localhost/activities/{$activity->id}/edit"
            )
            ->assertNotFound();
    }

    public function test_activity_can_be_completed(): void
    {
        $tenant = $this->tenant(
            'activity-http-complete'
        );

        $user = $this->user(
            $tenant,
            'complete@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_UPDATE
        );

        $activity = $this->activity(
            $tenant,
            'Concluir'
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/activities/{$activity->id}/complete"
            )
            ->assertRedirect();

        app(TenantContext::class)->set($tenant);

        $activity->refresh();

        $this->assertSame(
            ActivityStatus::COMPLETED,
            $activity->status
        );

        $this->assertNotNull(
            $activity->completed_at
        );
    }

    public function test_completed_activity_can_be_reopened(): void
    {
        $tenant = $this->tenant(
            'activity-http-reopen'
        );

        $user = $this->user(
            $tenant,
            'reopen@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_UPDATE
        );

        $activity = $this->activity(
            $tenant,
            'Reabrir'
        );

        app(ActivityService::class)->complete(
            $activity
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/activities/{$activity->id}/reopen"
            )
            ->assertRedirect();

        app(TenantContext::class)->set($tenant);

        $activity->refresh();

        $this->assertSame(
            ActivityStatus::PENDING,
            $activity->status
        );

        $this->assertNull(
            $activity->completed_at
        );
    }

    public function test_activity_can_be_cancelled(): void
    {
        $tenant = $this->tenant(
            'activity-http-cancel'
        );

        $user = $this->user(
            $tenant,
            'cancel@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_UPDATE
        );

        $activity = $this->activity(
            $tenant,
            'Cancelar'
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/activities/{$activity->id}/cancel"
            )
            ->assertRedirect();

        app(TenantContext::class)->set($tenant);

        $activity->refresh();

        $this->assertSame(
            ActivityStatus::CANCELLED,
            $activity->status
        );
    }

    public function test_delete_requires_delete_permission(): void
    {
        $tenant = $this->tenant(
            'activity-http-delete'
        );

        $user = $this->user(
            $tenant,
            'delete@activities.local'
        );

        $this->grant(
            $user,
            PermissionEnum::ACTIVITIES_VIEW
        );

        $activity = $this->activity(
            $tenant,
            'Preservada'
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/activities/{$activity->id}"
            )
            ->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            Activity::query()
                ->whereKey($activity->id)
                ->exists()
        );
    }
}
