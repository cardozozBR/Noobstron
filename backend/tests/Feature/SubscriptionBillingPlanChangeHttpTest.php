<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionBillingPlanChangeHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_stripe_customer_can_change_plan(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_http_change_123' =>
                Http::sequence()
                    ->push([
                        'id' => 'sub_http_change_123',
                        'items' => [
                            'data' => [
                                [
                                    'id' => 'si_http_change_123',
                                ],
                            ],
                        ],
                    ], 200)
                    ->push([
                        'id' => 'sub_http_change_123',
                        'status' => 'active',
                    ], 200),
        ]);

        $tenant = $this->tenant(
            'billing-plan-change-success'
        );

        $user = $this->user(
            $tenant,
            'billing-plan-change-user'
        );

        $start = $this->plan(
            'start-http-change',
            'Start',
            9900,
            'price_start_http'
        );

        $pro = $this->plan(
            'pro-http-change',
            'Pro',
            24900,
            'price_pro_http'
        );

        $subscription = $this->subscription(
            $tenant,
            $start,
            'stripe',
            'sub_http_change_123'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/billing/change-plan",
                [
                    'plan_id' => $pro->id,
                ]
            );

        $response
            ->assertRedirect(
                route('billing.index')
            )
            ->assertSessionHas(
                'success',
                'Plano alterado com sucesso.'
            );

        $this->assertSame(
            $pro->id,
            $subscription->refresh()->plan_id
        );
    }

    public function test_invalid_plan_id_is_rejected(): void
    {
        $tenant = $this->tenant(
            'billing-plan-change-invalid'
        );

        $user = $this->user(
            $tenant,
            'billing-plan-invalid-user'
        );

        $start = $this->plan(
            'start-http-invalid',
            'Start',
            9900,
            'price_start_invalid'
        );

        $subscription = $this->subscription(
            $tenant,
            $start,
            'stripe',
            'sub_http_invalid_123'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/billing/change-plan",
                [
                    'plan_id' => 0,
                ]
            );

        $response
            ->assertRedirect(
                route('billing.index')
            )
            ->assertSessionHas(
                'error',
                'Plano inválido.'
            );

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );
    }

    public function test_non_stripe_subscription_cannot_change_plan_through_http(): void
    {
        Http::fake();

        $tenant = $this->tenant(
            'billing-plan-change-non-stripe'
        );

        $user = $this->user(
            $tenant,
            'billing-plan-non-stripe-user'
        );

        $start = $this->plan(
            'start-http-non-stripe',
            'Start',
            9900,
            'price_start_non_stripe'
        );

        $pro = $this->plan(
            'pro-http-non-stripe',
            'Pro',
            24900,
            'price_pro_non_stripe'
        );

        $subscription = $this->subscription(
            $tenant,
            $start,
            'mercado_pago',
            'mp_http_123'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/billing/change-plan",
                [
                    'plan_id' => $pro->id,
                ]
            );

        $response
            ->assertRedirect(
                route('billing.index')
            )
            ->assertSessionHas(
                'error',
                'A troca de plano automática está disponível apenas para assinaturas Stripe.'
            );

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );

        Http::assertNothingSent();
    }

    public function test_stripe_rejection_does_not_change_local_plan_through_http(): void
    {
        config()->set(
            'services.stripe.secret_key',
            'TEST_STRIPE_SECRET'
        );

        config()->set(
            'services.stripe.base_url',
            'https://api.stripe.test'
        );

        Http::fake([
            'https://api.stripe.test/v1/subscriptions/sub_http_reject_123' =>
                Http::sequence()
                    ->push([
                        'id' => 'sub_http_reject_123',
                        'items' => [
                            'data' => [
                                [
                                    'id' => 'si_http_reject_123',
                                ],
                            ],
                        ],
                    ], 200)
                    ->push([
                        'error' => [
                            'message' =>
                                'Rejected plan change.',
                        ],
                    ], 400),
        ]);

        $tenant = $this->tenant(
            'billing-plan-change-reject'
        );

        $user = $this->user(
            $tenant,
            'billing-plan-reject-user'
        );

        $start = $this->plan(
            'start-http-reject',
            'Start',
            9900,
            'price_start_reject'
        );

        $pro = $this->plan(
            'pro-http-reject',
            'Pro',
            24900,
            'price_pro_reject'
        );

        $subscription = $this->subscription(
            $tenant,
            $start,
            'stripe',
            'sub_http_reject_123'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/billing/change-plan",
                [
                    'plan_id' => $pro->id,
                ]
            );

        $response
            ->assertRedirect(
                route('billing.index')
            )
            ->assertSessionHas(
                'error',
                'Stripe rejected subscription plan change.'
            );

        $this->assertSame(
            $start->id,
            $subscription->refresh()->plan_id
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
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

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' =>
                $tenant->slug
                . '-'
                . str($name)->slug()
                . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'admin',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function plan(
        string $code,
        string $name,
        int $amountMinor,
        ?string $stripePriceId,
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
            'stripe_price_id' =>
                $stripePriceId,
        ]);

        return $plan;
    }

    private function subscription(
        Tenant $tenant,
        Plan $plan,
        string $provider,
        string $externalReference,
    ): Subscription {
        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => $provider,
            'external_reference' =>
                $externalReference,
            'paid_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 12:00:00 UTC'
                ),
            'current_period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'current_period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
        ]);
    }
}