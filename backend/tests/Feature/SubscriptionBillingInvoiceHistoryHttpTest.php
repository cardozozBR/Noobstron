<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionBillingInvoiceHistoryHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_see_subscription_invoice_history(): void
    {
        $tenant = $this->tenant(
            'billing-invoice-history'
        );

        $user = $this->user(
            $tenant,
            'billing-invoice-history-user'
        );

        $subscription = $this->subscription(
            $tenant
        );

        SubscriptionInvoice::query()->create([
            'subscription_id' =>
                $subscription->id,
            'provider' => 'stripe',
            'external_invoice_id' =>
                'in_history_123',
            'status' => 'paid',
            'currency' => 'BRL',
            'amount_due' => 9900,
            'amount_paid' => 9900,
            'amount_remaining' => 0,
            'billing_reason' =>
                'subscription_cycle',
            'period_start' =>
                CarbonImmutable::parse(
                    '2026-08-20 00:00:00 UTC'
                ),
            'period_end' =>
                CarbonImmutable::parse(
                    '2026-09-20 00:00:00 UTC'
                ),
            'paid_at' =>
                CarbonImmutable::parse(
                    '2026-08-20 23:53:00 UTC'
                ),
            'hosted_invoice_url' =>
                'https://invoice.test/history',
            'invoice_pdf' =>
                'https://invoice.test/history.pdf',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/billing"
            );

        $response
            ->assertOk()
            ->assertSee(
                'Histórico de faturas'
            )
            ->assertSee(
                'BRL'
            )
            ->assertSee(
                '99,00'
            )
            ->assertSee(
                'Pago'
            )
            ->assertSee(
                '20/08/2026'
            )
            ->assertSee(
                '20/09/2026'
            )
            ->assertSee(
                'https://invoice.test/history',
                false
            )
            ->assertSee(
                'https://invoice.test/history.pdf',
                false
            );
    }

    public function test_billing_invoice_history_does_not_show_other_tenant_invoice(): void
    {
        $tenantA = $this->tenant(
            'billing-invoice-tenant-a'
        );

        $userA = $this->user(
            $tenantA,
            'billing-invoice-user-a'
        );

        $subscriptionA = $this->subscription(
            $tenantA
        );

        SubscriptionInvoice::query()->create([
            'subscription_id' =>
                $subscriptionA->id,
            'provider' => 'stripe',
            'external_invoice_id' =>
                'in_tenant_a_123',
            'status' => 'paid',
            'currency' => 'BRL',
            'amount_due' => 9900,
            'amount_paid' => 9900,
            'amount_remaining' => 0,
        ]);

        $tenantB = $this->tenant(
            'billing-invoice-tenant-b'
        );

        $subscriptionB = $this->subscription(
            $tenantB
        );

        SubscriptionInvoice::query()->create([
            'subscription_id' =>
                $subscriptionB->id,
            'provider' => 'stripe',
            'external_invoice_id' =>
                'in_tenant_b_secret',
            'status' => 'paid',
            'currency' => 'BRL',
            'amount_due' => 49900,
            'amount_paid' => 49900,
            'amount_remaining' => 0,
            'hosted_invoice_url' =>
                'https://invoice.test/tenant-b-secret',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $response = $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/billing"
            );

        $response
    ->assertOk()
    ->assertSee(
        '99,00'
    )
    ->assertDontSee(
        '499,00'
    )
    ->assertDontSee(
        'https://invoice.test/tenant-b-secret',
        false
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

    private function subscription(
        Tenant $tenant
    ): Subscription {
        $plan = Plan::query()->create([
            'code' => uniqid(
                'billing-invoice-plan-',
                false
            ),
            'name' => 'Billing Invoice Plan',
            'active' => true,
        ]);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => 'stripe',
            'external_reference' =>
                uniqid(
                    'sub_billing_invoice_',
                    false
                ),
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