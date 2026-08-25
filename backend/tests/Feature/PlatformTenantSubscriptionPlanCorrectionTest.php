<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformTenantSubscriptionPlanCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_correct_subscription_plan(): void
    {
        config([
            'services.stripe.secret_key' =>
                'sk_test_platform_plan_correction',
            'services.stripe.base_url' =>
                'https://api.stripe.test',
        ]);

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_plan_correction_123' =>
                Http::sequence()
                    ->push([
                        'id' => 'sub_plan_correction_123',
                        'status' => 'active',
                        'items' => [
                            'data' => [
                                [
                                    'id' => 'si_plan_correction_123',
                                    'price' => [
                                        'id' => 'price_start_test',
                                    ],
                                ],
                            ],
                        ],
                    ], 200)
                    ->push([
                        'id' => 'sub_plan_correction_123',
                        'status' => 'active',
                    ], 200),
        ]);

        $admin = $this->admin();
        $tenant = $this->tenant();

        $start = $this->plan(
            'start-correction',
            'Start Correction',
            'price_start_test',
            9900
        );

        $pro = $this->plan(
            'pro-correction',
            'Pro Correction',
            'price_pro_test',
            19900
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $start->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' =>
                'sub_plan_correction_123',
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.tenants.subscription.correct-plan',
                    $tenant
                ),
                [
                    'plan_id' => $pro->id,
                    'reason' =>
                        'Administrative plan correction.',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.tenants.show',
                    $tenant
                )
            )
            ->assertSessionHas('success');

        $subscription->refresh();

        $this->assertSame(
            $pro->id,
            $subscription->plan_id
        );

        $this->assertSame(
            19900,
            $subscription->amount_minor
        );

        $this->assertSame(
            'BRL',
            $subscription->currency
        );

        Http::assertSent(
            fn ($request): bool =>
                $request->method() === 'GET'
                && $request->url()
                    === 'https://api.stripe.test/v1/subscriptions/sub_plan_correction_123'
        );

        Http::assertSent(
            function ($request): bool {
                return $request->method() === 'POST'
                    && $request->url()
                        === 'https://api.stripe.test/v1/subscriptions/sub_plan_correction_123'
                    && (string) $request[
                        'items[0][id]'
                    ] === 'si_plan_correction_123'
                    && (string) $request[
                        'items[0][price]'
                    ] === 'price_pro_test'
                    && (string) $request[
                        'proration_behavior'
                    ] === 'create_prorations';
            }
        );

        $log = PlatformAdminAuditLog::query()
            ->where(
                'action',
                'subscription.plan_corrected'
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
            $start->id,
            $log->before_state['plan_id']
        );

        $this->assertSame(
            $pro->id,
            $log->after_state['plan_id']
        );

        $this->assertSame(
            'Administrative plan correction.',
            $log->reason
        );
    }

    public function test_stripe_failure_does_not_change_local_plan(): void
    {
        config([
            'services.stripe.secret_key' =>
                'sk_test_platform_plan_correction',
            'services.stripe.base_url' =>
                'https://api.stripe.test',
        ]);

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_plan_failure_123' =>
                Http::response([
                    'error' => [
                        'message' => 'Stripe failure.',
                    ],
                ], 500),
        ]);

        $tenant = $this->tenant();

        $start = $this->plan(
            'start-failure',
            'Start Failure',
            'price_start_failure',
            9900
        );

        $pro = $this->plan(
            'pro-failure',
            'Pro Failure',
            'price_pro_failure',
            19900
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $start->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' =>
                'sub_plan_failure_123',
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs(
            $this->admin(),
            'platform'
        )
            ->post(
                route(
                    'platform.tenants.subscription.correct-plan',
                    $tenant
                ),
                [
                    'plan_id' => $pro->id,
                    'reason' => 'Failure test.',
                ]
            )
            ->assertSessionHasErrors(
                'subscription'
            );

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );

        $log = PlatformAdminAuditLog::query()
            ->where(
                'action',
                'subscription.plan_corrected'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            'failure',
            $log->result
        );
    }

    public function test_reason_is_required(): void
    {
        $tenant = $this->tenant();

        $this->actingAs(
            $this->admin(),
            'platform'
        )
            ->post(
                route(
                    'platform.tenants.subscription.correct-plan',
                    $tenant
                ),
                [
                    'plan_id' => 1,
                ]
            )
            ->assertSessionHasErrors('reason');
    }

    public function test_non_stripe_subscription_cannot_be_corrected(): void
    {
        $tenant = $this->tenant();

        $start = $this->plan(
            'mp-start',
            'MP Start',
            'price_mp_start',
            9900
        );

        $pro = $this->plan(
            'mp-pro',
            'MP Pro',
            'price_mp_pro',
            19900
        );

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $start->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'mp-sub-123',
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs(
            $this->admin(),
            'platform'
        )
            ->post(
                route(
                    'platform.tenants.subscription.correct-plan',
                    $tenant
                ),
                [
                    'plan_id' => $pro->id,
                    'reason' => 'Invalid provider.',
                ]
            )
            ->assertSessionHasErrors(
                'subscription'
            );

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );
    }

    public function test_active_stripe_subscription_shows_plan_correction_action(): void
    {
        $tenant = $this->tenant();

        $start = $this->plan(
            'ui-start',
            'UI Start',
            'price_ui_start',
            9900
        );

        $this->plan(
            'ui-pro',
            'UI Pro',
            'price_ui_pro',
            19900
        );

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $start->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_ui_plan_123',
            'currency' => 'BRL',
            'amount_minor' => 9900,
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
                'Corrigir plano da assinatura'
            )
            ->assertSee('UI Pro')
            ->assertSee('Motivo da correção');
    }

    public function test_subscription_with_scheduled_cancellation_cannot_be_corrected(): void
{
    $tenant = $this->tenant();

    $start = $this->plan(
        'scheduled-start',
        'Scheduled Start',
        'price_scheduled_start',
        9900
    );

    $pro = $this->plan(
        'scheduled-pro',
        'Scheduled Pro',
        'price_scheduled_pro',
        19900
    );

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $start->id,
        'status' => SubscriptionStatus::ACTIVE,
        'payment_provider' => 'stripe',
        'external_reference' =>
            'sub_scheduled_plan_123',
        'currency' => 'BRL',
        'amount_minor' => 9900,
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'cancel_at' => now()->addMonth(),
    ]);

    $this->actingAs(
        $this->admin(),
        'platform'
    )
        ->post(
            route(
                'platform.tenants.subscription.correct-plan',
                $tenant
            ),
            [
                'plan_id' => $pro->id,
                'reason' =>
                    'Should not change scheduled cancellation.',
            ]
        )
        ->assertSessionHasErrors(
            'subscription'
        );

    $this->assertSame(
        $start->id,
        $subscription->refresh()->plan_id
    );

    $this->assertDatabaseMissing(
        'platform_admin_audit_logs',
        [
            'action' =>
                'subscription.plan_corrected',
            'tenant_id' =>
                $tenant->id,
        ]
    );
}

    public function test_guest_cannot_correct_subscription_plan(): void
    {
        $tenant = $this->tenant();

        $this->post(
            route(
                'platform.tenants.subscription.correct-plan',
                $tenant
            ),
            [
                'plan_id' => 1,
                'reason' => 'Unauthorized.',
            ]
        )
            ->assertRedirect(
                route('platform.login')
            );

        $this->assertDatabaseMissing(
            'platform_admin_audit_logs',
            [
                'action' =>
                    'subscription.plan_corrected',
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
                'plan-correction-admin@example.test',
            'password' =>
                Hash::make('secret-password'),
            'is_active' => true,
        ]);
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' =>
                'Plan Correction Tenant',
            'slug' =>
                'plan-correction-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);
    }

    private function plan(
        string $code,
        string $name,
        string $stripePriceId,
        int $amountMinor,
    ): Plan {
        $plan = Plan::query()->create([
            'code' => $code,
            'name' => $name,
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => $amountMinor,
            'stripe_price_id' => $stripePriceId,
        ]);

        return $plan;
    }
}