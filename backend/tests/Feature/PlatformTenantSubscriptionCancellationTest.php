<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformTenantSubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_schedule_subscription_cancellation(): void
    {
        config([
            'services.stripe.secret_key' =>
                'sk_test_platform_cancel',
            'services.stripe.base_url' =>
                'https://api.stripe.test',
        ]);

        $periodEnd = CarbonImmutable::parse(
            '2026-09-25 12:00:00 UTC'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_platform_cancel_123' =>
                Http::response([
                    'id' =>
                        'sub_platform_cancel_123',
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                    'cancel_at' =>
                        $periodEnd->timestamp,
                    'current_period_end' =>
                        $periodEnd->timestamp,
                ], 200),
        ]);

        $admin = $this->admin();
        $tenant = $this->tenant();

        $plan = Plan::query()->create([
            'code' => 'platform-cancel-plan',
            'name' => 'Platform Cancel Plan',
            'active' => true,
        ]);

        $subscription =
            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' =>
                    SubscriptionStatus::ACTIVE,
                'payment_provider' => 'stripe',
                'external_reference' =>
                    'sub_platform_cancel_123',
                'current_period_start' =>
                    CarbonImmutable::parse(
                        '2026-08-25 12:00:00 UTC'
                    ),
                'current_period_end' =>
                    $periodEnd,
            ]);

        $this->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.tenants.subscription.cancel',
                    $tenant
                ),
                [
                    'reason' =>
                        'Administrative cancellation.',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.tenants.show',
                    $tenant
                )
            )
            ->assertSessionHas('success');

        Http::assertSent(
            function ($request): bool {
                return $request->method()
                        === 'POST'
                    && $request->url()
                        === 'https://api.stripe.test/v1/subscriptions/sub_platform_cancel_123'
                    && (string) $request[
                        'cancel_at_period_end'
                    ] === 'true';
            }
        );

        $subscription->refresh();

        $this->assertSame(
            SubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            '2026-09-25 12:00:00',
            $subscription
                ->cancel_at
                ->utc()
                ->format('Y-m-d H:i:s')
        );

        $this->assertNull(
            $subscription->canceled_at
        );

        $log = PlatformAdminAuditLog::query()
            ->where(
                'action',
                'subscription.cancellation_scheduled'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $admin->id,
            $log->platform_admin_id
        );

        $this->assertSame(
            $tenant->id,
            $log->tenant_id
        );

        $this->assertSame(
            Subscription::class,
            $log->entity_type
        );

        $this->assertSame(
            (string) $subscription->id,
            $log->entity_id
        );

        $this->assertSame(
            'active',
            $log->before_state['status']
        );

        $this->assertSame(
            'active',
            $log->after_state['status']
        );

        $this->assertNull(
            $log->before_state['cancel_at']
        );

        $this->assertNotNull(
            $log->after_state['cancel_at']
        );

        $this->assertSame(
            'Administrative cancellation.',
            $log->reason
        );
    }

    public function test_non_stripe_subscription_cannot_be_scheduled_for_cancellation(): void
{
    $admin = $this->admin();
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'code' => 'non-stripe-plan',
        'name' => 'Non Stripe Plan',
        'active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'mercado_pago',
        'external_reference' => 'mp-sub-123',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($admin, 'platform')
        ->post(
            route(
                'platform.tenants.subscription.cancel',
                $tenant
            ),
            [
                'reason' => 'Invalid provider.',
            ]
        )
        ->assertSessionHasErrors('subscription');

    $this->assertNull(
        $subscription->refresh()->cancel_at
    );

    $this->assertDatabaseMissing(
        'platform_admin_audit_logs',
        [
            'action' =>
                'subscription.cancellation_scheduled',
            'tenant_id' => $tenant->id,
        ]
    );
}

public function test_subscription_with_existing_cancel_at_cannot_be_scheduled_again(): void
{
    $admin = $this->admin();
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'code' => 'already-cancel-plan',
        'name' => 'Already Cancel Plan',
        'active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'stripe',
        'external_reference' => 'sub_already_cancel_123',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'cancel_at' => now()->addMonth(),
    ]);

    $this->actingAs($admin, 'platform')
        ->post(
            route(
                'platform.tenants.subscription.cancel',
                $tenant
            ),
            [
                'reason' => 'Duplicate cancellation.',
            ]
        )
        ->assertSessionHasErrors('subscription');

    $this->assertNotNull(
        $subscription->refresh()->cancel_at
    );
}

public function test_stripe_failure_records_failed_audit_and_does_not_schedule_locally(): void
{
    config([
        'services.stripe.secret_key' =>
            'sk_test_platform_cancel',
        'services.stripe.base_url' =>
            'https://api.stripe.test',
    ]);

    Http::fake([
        'https://api.stripe.test/v1/subscriptions/sub_platform_fail_123' =>
            Http::response([
                'error' => [
                    'message' => 'Stripe failure.',
                ],
            ], 500),
    ]);

    $admin = $this->admin();
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'code' => 'platform-fail-plan',
        'name' => 'Platform Fail Plan',
        'active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'stripe',
        'external_reference' => 'sub_platform_fail_123',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs($admin, 'platform')
        ->post(
            route(
                'platform.tenants.subscription.cancel',
                $tenant
            ),
            [
                'reason' => 'Failure test.',
            ]
        )
        ->assertSessionHasErrors('subscription');

    $this->assertNull(
        $subscription->refresh()->cancel_at
    );

    $log = PlatformAdminAuditLog::query()
        ->where(
            'action',
            'subscription.cancellation_scheduled'
        )
        ->latest('id')
        ->firstOrFail();

    $this->assertSame(
        'failure',
        $log->result
    );

    $this->assertSame(
        $tenant->id,
        $log->tenant_id
    );
}

    public function test_reason_is_required(): void
    {
        $admin = $this->admin();
        $tenant = $this->tenant();

        $this->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.tenants.subscription.cancel',
                    $tenant
                )
            )
            ->assertSessionHasErrors('reason');
    }

    public function test_active_stripe_subscription_shows_cancellation_action(): void
{
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'code' => 'ui-cancel-plan',
        'name' => 'UI Cancel Plan',
        'active' => true,
    ]);

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'stripe',
        'external_reference' => 'sub_ui_cancel_123',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->actingAs(
        $this->admin(),
        'platform'
    )
        ->get(
            route(
                'platform.tenants.show',
                $tenant
            )
        )
        ->assertOk()
        ->assertSee(
            'Cancelar assinatura ao fim do período'
        )
        ->assertSee(
            'Motivo do cancelamento'
        );
}

public function test_scheduled_subscription_hides_cancellation_action_and_shows_date(): void
{
    $tenant = $this->tenant();

    $plan = Plan::query()->create([
        'code' => 'ui-scheduled-plan',
        'name' => 'UI Scheduled Plan',
        'active' => true,
    ]);

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'stripe',
        'external_reference' =>
            'sub_ui_scheduled_123',
        'current_period_start' => now(),
        'current_period_end' =>
            CarbonImmutable::parse(
                '2026-09-25 12:00:00 UTC'
            ),
        'cancel_at' =>
            CarbonImmutable::parse(
                '2026-09-25 12:00:00 UTC'
            ),
    ]);

    $this->actingAs(
        $this->admin(),
        'platform'
    )
        ->get(
            route(
                'platform.tenants.show',
                $tenant
            )
        )
        ->assertOk()
        ->assertSee('Cancelamento agendado para')
        ->assertDontSee(
            'Cancelar assinatura ao fim do período'
        );
}

    public function test_guest_cannot_schedule_subscription_cancellation(): void
    {
        $tenant = $this->tenant();

        $this->post(
            route(
                'platform.tenants.subscription.cancel',
                $tenant
            ),
            [
                'reason' =>
                    'Unauthorized cancellation.',
            ]
        )
            ->assertRedirect(
                route('platform.login')
            );

        $this->assertDatabaseMissing(
            'platform_admin_audit_logs',
            [
                'action' =>
                    'subscription.cancellation_scheduled',
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    private function admin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' =>
                'subscription-cancel-admin@example.test',
            'password' =>
                Hash::make('secret-password'),
            'is_active' => true,
        ]);
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' =>
                'Subscription Cancellation Tenant',
            'slug' =>
                'subscription-cancellation-tenant',
            'status' => 'active',
        ]);
    }
}