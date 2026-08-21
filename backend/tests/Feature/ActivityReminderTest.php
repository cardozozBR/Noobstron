<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ActivityDueReminder;
use App\Services\ActivityService;
use App\Services\ActivityReminderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityReminderTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)
            ->set($tenant);

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)
            ->set($tenant);

        return User::create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Responsible User',
            'email' => $email,
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' => 'user',
        ]);
    }

    public function test_pending_activity_due_within_24_hours_notifies_responsible(): void
    {
        Notification::fake();

        $tenant = $this->tenant(
            'reminder-due'
        );

        $user = $this->user(
            $tenant,
            'due@activities.local'
        );

        $activity = app(
            ActivityService::class
        )->create([
            'type' =>
                ActivityType::TASK,
            'status' =>
                ActivityStatus::PENDING,
            'title' =>
                'Enviar contrato',
            'responsible_user_id' =>
                $user->id,
            'due_at' =>
                now()->addHours(4),
        ]);

        $count = app(
            ActivityReminderService::class
        )->dispatchForCurrentTenant();

        $this->assertSame(
            1,
            $count
        );

        Notification::assertSentTo(
            $user,
            ActivityDueReminder::class
        );

        $this->assertNotNull(
            $activity
                ->fresh()
                ->reminder_notified_at
        );
    }

    public function test_activity_outside_window_is_not_notified(): void
    {
        Notification::fake();

        $tenant = $this->tenant(
            'reminder-future'
        );

        $user = $this->user(
            $tenant,
            'future@activities.local'
        );

        app(ActivityService::class)
            ->create([
                'title' =>
                    'Atividade futura',
                'responsible_user_id' =>
                    $user->id,
                'due_at' =>
                    now()->addDays(3),
            ]);

        $count = app(
            ActivityReminderService::class
        )->dispatchForCurrentTenant();

        $this->assertSame(
            0,
            $count
        );

        Notification::assertNothingSent();
    }

    public function test_completed_activity_is_not_notified(): void
    {
        Notification::fake();

        $tenant = $this->tenant(
            'reminder-complete'
        );

        $user = $this->user(
            $tenant,
            'complete@activities.local'
        );

        app(ActivityService::class)
            ->create([
                'title' =>
                    'Já concluída',
                'status' =>
                    ActivityStatus::COMPLETED,
                'responsible_user_id' =>
                    $user->id,
                'due_at' =>
                    now()->addHours(2),
            ]);

        app(
            ActivityReminderService::class
        )->dispatchForCurrentTenant();

        Notification::assertNothingSent();
    }

    public function test_activity_without_responsible_is_not_notified(): void
    {
        Notification::fake();

        $this->tenant(
            'reminder-no-owner'
        );

        app(ActivityService::class)
            ->create([
                'title' =>
                    'Sem responsável',
                'due_at' =>
                    now()->addHours(2),
            ]);

        app(
            ActivityReminderService::class
        )->dispatchForCurrentTenant();

        Notification::assertNothingSent();
    }

    public function test_same_activity_is_not_notified_twice(): void
    {
        Notification::fake();

        $tenant = $this->tenant(
            'reminder-once'
        );

        $user = $this->user(
            $tenant,
            'once@activities.local'
        );

        app(ActivityService::class)
            ->create([
                'title' =>
                    'Notificar uma vez',
                'responsible_user_id' =>
                    $user->id,
                'due_at' =>
                    now()->addHour(),
            ]);

        $service = app(
            ActivityReminderService::class
        );

        $this->assertSame(
            1,
            $service->dispatchForCurrentTenant()
        );

        $this->assertSame(
            0,
            $service->dispatchForCurrentTenant()
        );

        Notification::assertSentToTimes(
            $user,
            ActivityDueReminder::class,
            1
        );
    }

    public function test_reminders_are_isolated_by_tenant(): void
    {
        Notification::fake();

        $tenantA = $this->tenant(
            'reminder-a'
        );

        $userA = $this->user(
            $tenantA,
            'a@activities.local'
        );

        app(ActivityService::class)
            ->create([
                'title' => 'Tenant A',
                'responsible_user_id' =>
                    $userA->id,
                'due_at' =>
                    now()->addHour(),
            ]);

        $tenantB = $this->tenant(
            'reminder-b'
        );

        $userB = $this->user(
            $tenantB,
            'b@activities.local'
        );

        app(ActivityService::class)
            ->create([
                'title' => 'Tenant B',
                'responsible_user_id' =>
                    $userB->id,
                'due_at' =>
                    now()->addHour(),
            ]);

        app(TenantContext::class)
            ->set($tenantA);

        app(
            ActivityReminderService::class
        )->dispatchForCurrentTenant();

        Notification::assertSentTo(
            $userA,
            ActivityDueReminder::class
        );

        Notification::assertNotSentTo(
            $userB,
            ActivityDueReminder::class
        );
    }
}
