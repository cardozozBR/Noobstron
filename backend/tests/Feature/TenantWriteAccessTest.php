<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Permission as PermissionEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionBillingService;
use App\Services\TenantContext;
use App\Services\TenantWriteAccessService;
use App\Support\TenantCapabilities;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantWriteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_legacy_tenant_without_commercial_history_can_write(): void
    {
        $tenant = $this->tenant('write-legacy');

        $this->assertTrue(
            app(TenantWriteAccessService::class)
                ->allowed($tenant)
        );
    }

    public function test_active_trial_can_write(): void
    {
        $tenant = $this->tenant(
            'write-trial',
            trialStartsAt: CarbonImmutable::parse(
                '2026-08-20 00:00:00 UTC'
            ),
            trialEndsAt: CarbonImmutable::parse(
                '2026-08-30 00:00:00 UTC'
            ),
        );

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-23 12:00:00 UTC'
            )
        );

        try {
            $this->assertTrue(
                app(TenantWriteAccessService::class)
                    ->allowed($tenant)
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_active_paid_subscription_can_write(): void
    {
        $tenant = $this->tenant('write-active-paid');
        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_write_active_paid',
                'card',
            );

        $this->assertTrue(
            app(TenantWriteAccessService::class)
                ->allowed($tenant->refresh())
        );
    }

    public function test_cancelled_paid_subscription_cannot_write(): void
    {
        $tenant = $this->tenant('write-cancelled');
        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_write_cancelled',
                'card',
            );

        $subscription->forceFill([
            'status' => SubscriptionStatus::CANCELLED,
        ])->save();

        $this->assertFalse(
            app(TenantWriteAccessService::class)
                ->allowed($tenant->refresh())
        );
    }

    public function test_cancelled_paid_subscription_does_not_regain_write_access_from_old_active_trial_dates(): void
    {
        $tenant = $this->tenant(
            'write-cancelled-old-trial',
            trialStartsAt: CarbonImmutable::parse(
                '2026-08-23 00:43:53 America/Sao_Paulo'
            ),
            trialEndsAt: CarbonImmutable::parse(
                '2026-09-06 00:43:53 America/Sao_Paulo'
            ),
        );

        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_write_cancelled_old_trial',
                'card',
                CarbonImmutable::parse(
                    '2026-08-23 15:17:13 America/Sao_Paulo'
                ),
            );

        $subscription->forceFill([
            'status' => SubscriptionStatus::CANCELLED,
        ])->save();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-23 20:30:00 America/Sao_Paulo'
            )
        );

        try {
            $this->assertFalse(
                app(TenantWriteAccessService::class)
                    ->allowed($tenant->refresh())
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_expired_paid_subscription_cannot_write(): void
    {
        $tenant = $this->tenant('write-expired');
        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_write_expired',
                'card',
            );

        $subscription->forceFill([
            'status' => SubscriptionStatus::EXPIRED,
        ])->save();

        $this->assertFalse(
            app(TenantWriteAccessService::class)
                ->allowed($tenant->refresh())
        );
    }

    public function test_cancelled_subscription_can_read_leads_but_cannot_create_one(): void
    {
        $tenant = $this->tenant('write-readonly-http');
        $user = $this->user($tenant);

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $subscription = $this->subscription(
            $tenant,
            SubscriptionStatus::ACTIVE
        );

        app(SubscriptionBillingService::class)
            ->markPaid(
                $subscription,
                'stripe',
                'sub_write_readonly_http',
                'card',
            );

        $subscription->forceFill([
            'status' => SubscriptionStatus::CANCELLED,
        ])->save();

        app(TenantContext::class)->set($tenant);

        Lead::query()->create([
            'name' => 'Lead preservado',
        ]);

        $this
            ->actingAs($user)
            ->get(
                'http://write-readonly-http.localhost/leads'
            )
            ->assertOk()
            ->assertSee('Lead preservado');

        $this
            ->actingAs($user)
            ->post(
                'http://write-readonly-http.localhost/leads',
                [
                    'name' => 'Lead bloqueado',
                    'email' => 'blocked@example.local',
                    'phone' => '+5511999999999',
                    'status' => LeadStatus::NEW->value,
                    'source' => LeadSource::MANUAL->value,
                    'responsible_user_id' => null,
                    'tags' => [],
                    'notes' => null,
                ]
            )
            ->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertFalse(
            Lead::query()
                ->where(
                    'name',
                    'Lead bloqueado'
                )
                ->exists()
        );
    }

    private function tenant(
        string $slug,
        ?CarbonImmutable $trialStartsAt = null,
        ?CarbonImmutable $trialEndsAt = null,
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
            'trial_started_at' => $trialStartsAt,
            'trial_ends_at' => $trialEndsAt,
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            true
        );

        return $tenant;
    }

    private function user(Tenant $tenant): User
    {
        app(TenantContext::class)->set($tenant);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Write Access User',
            'email' => $tenant->slug . '@example.local',
            'password' => Hash::make('TesteSenha123'),
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

    private function subscription(
        Tenant $tenant,
        SubscriptionStatus $status,
    ): Subscription {
        $plan = Plan::query()->create([
            'code' =>
                $tenant->slug . '-plan',
            'name' =>
                ucfirst($tenant->slug) . ' Plan',
            'active' => true,
        ]);

        app(TenantContext::class)->set($tenant);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-01 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-01 00:00:00 UTC'
                ),
        ]);
    }
}
