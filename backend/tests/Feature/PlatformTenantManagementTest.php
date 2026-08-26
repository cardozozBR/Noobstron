<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Enums\Feature;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Enums\WhatsAppMessageStatus;
use App\Models\EmailMessage;
use App\Models\PaymentEventReceipt;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => uniqid('platform-', true)
                .'@example.test',
            'password' => Hash::make(
                'SenhaSegura123'
            ),
            'is_active' => true,
        ]);
    }

    private function tenant(
        string $name,
        string $slug,
        string $status = 'active'
    ): Tenant {
        return Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
            'trial_started_at' => '2026-08-10 12:00:00',
            'trial_ends_at' => '2026-08-24 12:00:00',
        ]);
    }

    private function plan(
        string $code,
        string $name
    ): Plan {
        return Plan::query()->create([
            'code' => $code,
            'name' => $name,
            'active' => true,
        ]);
    }

    private function subscribe(
        Tenant $tenant,
        Plan $plan,
        SubscriptionStatus $status =
            SubscriptionStatus::ACTIVE
    ): Subscription {
        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'current_period_start' => '2026-08-18 00:00:00',
            'current_period_end' => '2026-09-18 00:00:00',
        ]);
    }

    public function test_platform_admin_can_list_tenants_globally(): void
    {
        $first = $this->tenant(
            'Empresa Global A',
            'empresa-global-a'
        );

        $second = $this->tenant(
            'Empresa Global B',
            'empresa-global-b',
            'blocked'
        );

        DB::table('users')->insert([
            [
                'tenant_id' => $first->id,
                'name' => 'Pessoa A',
                'email' => 'pessoa-a@example.test',
                'password' => Hash::make('secret'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $first->id,
                'name' => 'Pessoa B',
                'email' => 'pessoa-b@example.test',
                'password' => Hash::make('secret'),
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants'
            );

        $response
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee($first->name)
            ->assertSee($second->name)
            ->assertSee('empresa-global-a')
            ->assertSee('blocked')
            ->assertSee('2');
    }

    public function test_tenant_list_shows_latest_subscription_plan_and_trial(): void
    {
        $tenant = $this->tenant(
            'Tenant Plano Pro',
            'tenant-plano-pro'
        );

        $plan = $this->plan(
            'pro-global',
            'Pro Global'
        );

        $this->subscribe(
            $tenant,
            $plan
        );

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants'
            );

        $response
            ->assertOk()
            ->assertSee('Pro Global')
            ->assertSee('active')
            ->assertSee('24/08/2026');
    }

    public function test_platform_admin_can_view_tenant_details(): void
    {
        $tenant = $this->tenant(
            'Tenant Detalhado',
            'tenant-detalhado'
        );

        $plan = $this->plan(
            'business-global',
            'Business Global'
        );

        $this->subscribe(
            $tenant,
            $plan,
            SubscriptionStatus::SUSPENDED
        );

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature' => Feature::USERS,
            'enabled' => true,
            'limit_value' => 12,
        ]);

        PlanUsageLimit::query()->create([
            'plan_id' => $plan->id,
            'metric' => UsageMetric::USERS,
            'limit_value' => 12,
        ]);

        DB::table('users')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Pessoa Detalhe',
            'email' => 'detalhe@example.test',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants/'
                .$tenant->id
            );

        $response
            ->assertOk()
            ->assertSee('Tenant Detalhado')
            ->assertSee('tenant-detalhado')
            ->assertSee('Business Global')
            ->assertSee('suspended')
            ->assertSee('Features')
            ->assertSee('Limites do plano')
            ->assertSee('users')
            ->assertSee('12')
            ->assertSee(
                route(
                    'platform.email-failures',
                    ['tenant_id' => $tenant->id]
                )
            )
            ->assertSee(
                route(
                    'platform.whatsapp-failures',
                    ['tenant_id' => $tenant->id]
                )
            )
            ->assertSee(
                route(
                    'platform.webhooks',
                    ['tenant_id' => $tenant->id]
                )
            );
    }

    public function test_tenant_admin_cannot_access_global_tenant_management(): void
    {
        $tenant = $this->tenant(
            'Tenant Comum',
            'tenant-comum-global'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Tenant',
            'email' => 'tenant-admin-global@example.test',
            'password' => Hash::make(
                'SenhaSegura123'
            ),
            'role' => Role::ADMIN,
        ]);

        $this
            ->actingAs($user)
            ->get(
                'http://localhost/platform/tenants'
            )
            ->assertRedirect(
                route('platform.login')
            );

        $this
            ->actingAs($user)
            ->get(
                'http://localhost/platform/tenants/'
                .$tenant->id
            )
            ->assertRedirect(
                route('platform.login')
            );
    }

    public function test_platform_tenant_management_does_not_require_tenant_context(): void
    {
        $tenant = $this->tenant(
            'Tenant Sem Contexto',
            'tenant-sem-contexto'
        );

        app(TenantContext::class)->clear();

        $admin = $this->platformAdmin();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                'http://localhost/platform/tenants'
            )
            ->assertOk();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                'http://localhost/platform/tenants/'
                .$tenant->id
            )
            ->assertOk();

    }

    public function test_platform_tenant_detail_isolates_operational_data_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'Tenant Operacional A',
            'tenant-operacional-a'
        );

        $tenantB = $this->tenant(
            'Tenant Operacional B',
            'tenant-operacional-b'
        );

        app(TenantContext::class)->set($tenantA);

        EmailMessage::query()->create([
            'tenant_id' => $tenantA->id,
            'to_email' => 'tenant-a@example.test',
            'to_name' => 'Tenant A',
            'subject' => 'EMAIL-FAILURE-TENANT-A',
            'body' => 'Mensagem exclusiva do tenant A.',
            'status' => EmailMessageStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => 'FALHA-EMAIL-EXCLUSIVA-A',
        ]);

        WhatsAppMessage::query()->create([
            'tenant_id' => $tenantA->id,
            'phone' => '5511999990001',
            'recipient_name' => 'Tenant A',
            'body' => 'WHATSAPP-FAILURE-TENANT-A',
            'status' => WhatsAppMessageStatus::FAILED,
            'direction' => 'outbound',
            'provider' => 'test',
            'failed_at' => now(),
            'failure_reason' => 'FALHA-WHATSAPP-EXCLUSIVA-A',
        ]);

        app(TenantContext::class)->set($tenantB);

        EmailMessage::query()->create([
            'tenant_id' => $tenantB->id,
            'to_email' => 'tenant-b@example.test',
            'to_name' => 'Tenant B',
            'subject' => 'EMAIL-FAILURE-TENANT-B',
            'body' => 'Mensagem exclusiva do tenant B.',
            'status' => EmailMessageStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => 'FALHA-EMAIL-EXCLUSIVA-B',
        ]);

        WhatsAppMessage::query()->create([
            'tenant_id' => $tenantB->id,
            'phone' => '5511999990002',
            'recipient_name' => 'Tenant B',
            'body' => 'WHATSAPP-FAILURE-TENANT-B',
            'status' => WhatsAppMessageStatus::FAILED,
            'direction' => 'outbound',
            'provider' => 'test',
            'failed_at' => now(),
            'failure_reason' => 'FALHA-WHATSAPP-EXCLUSIVA-B',
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants/'
                .$tenantA->id
            );

        $response
            ->assertOk()
            ->assertSee('Tenant Operacional A')
            ->assertSee('EMAIL-FAILURE-TENANT-A')
            ->assertSee('FALHA-EMAIL-EXCLUSIVA-A')
            ->assertSee('WHATSAPP-FAILURE-TENANT-A')
            ->assertSee('FALHA-WHATSAPP-EXCLUSIVA-A')
            ->assertDontSee('EMAIL-FAILURE-TENANT-B')
            ->assertDontSee('FALHA-EMAIL-EXCLUSIVA-B')
            ->assertDontSee('WHATSAPP-FAILURE-TENANT-B')
            ->assertDontSee('FALHA-WHATSAPP-EXCLUSIVA-B');
    }

    public function test_platform_tenant_detail_shows_subscription_billing_and_related_webhooks(): void
    {
        $tenantA = $this->tenant(
            'Tenant Billing A',
            'tenant-billing-a'
        );

        $tenantB = $this->tenant(
            'Tenant Billing B',
            'tenant-billing-b'
        );

        $plan = $this->plan(
            'phase7-billing-plan',
            'Plano Phase 7'
        );

        $this->subscribe(
            $tenantA,
            $plan,
            SubscriptionStatus::ACTIVE
        );

        $this->subscribe(
            $tenantB,
            $plan,
            SubscriptionStatus::ACTIVE
        );

        $subscriptionA = Subscription::query()
            ->where(
                'tenant_id',
                $tenantA->id
            )
            ->latest('id')
            ->firstOrFail();

        $subscriptionB = Subscription::query()
            ->where(
                'tenant_id',
                $tenantB->id
            )
            ->latest('id')
            ->firstOrFail();

        $subscriptionA->update([
            'payment_provider' => 'stripe',
            'external_reference' => 'sub-phase7-tenant-a',
            'currency' => 'BRL',
            'amount_minor' => 19900,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $subscriptionB->update([
            'payment_provider' => 'stripe',
            'external_reference' => 'sub-phase7-tenant-b-secret',
            'currency' => 'BRL',
            'amount_minor' => 49900,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        SubscriptionInvoice::query()->create([
            'subscription_id' => $subscriptionA->id,
            'provider' => 'stripe',
            'external_invoice_id' => 'in-phase7-tenant-a',
            'status' => 'paid',
            'currency' => 'BRL',
            'amount_due' => 19900,
            'amount_paid' => 19900,
            'amount_remaining' => 0,
            'billing_reason' => 'subscription_cycle',
            'paid_at' => now(),
        ]);

        SubscriptionInvoice::query()->create([
            'subscription_id' => $subscriptionB->id,
            'provider' => 'stripe',
            'external_invoice_id' => 'in-phase7-tenant-b-secret',
            'status' => 'paid',
            'currency' => 'BRL',
            'amount_due' => 49900,
            'amount_paid' => 49900,
            'amount_remaining' => 0,
            'billing_reason' => 'subscription_cycle',
            'paid_at' => now(),
        ]);

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt-phase7-tenant-a',
            'event_type' => 'invoice.payment_succeeded',
            'external_reference' => 'sub-phase7-tenant-a',
            'status' => 'processed',
            'attempts' => 1,
            'last_error' => null,
            'processed_at' => now(),
        ]);

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt-phase7-tenant-b-secret',
            'event_type' => 'invoice.payment_failed',
            'external_reference' => 'sub-phase7-tenant-b-secret',
            'status' => 'failed',
            'attempts' => 2,
            'last_error' => 'SECRET-WEBHOOK-ERROR-B',
            'processed_at' => null,
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs(
                $this->platformAdmin(),
                'platform'
            )
            ->get(
                'http://localhost/platform/tenants/'
                .$tenantA->id
            );

        $response
            ->assertOk()
            ->assertSee('Histórico de assinaturas')
            ->assertSee('Plano Phase 7')
            ->assertSee('sub-phase7-tenant-a')
            ->assertSee('Cobrança')
            ->assertSee('in-phase7-tenant-a')
            ->assertSee('BRL')
            ->assertSee('199,00')
            ->assertSee('Webhooks relacionados')
            ->assertSee('invoice.payment_succeeded')
            ->assertDontSee(
                'sub-phase7-tenant-b-secret'
            )
            ->assertDontSee(
                'in-phase7-tenant-b-secret'
            )
            ->assertDontSee(
                'invoice.payment_failed'
            )
            ->assertDontSee(
                'SECRET-WEBHOOK-ERROR-B'
            );
    }

    public function test_platform_error_state_component_displays_errors(): void
    {
        $errors = new \Illuminate\Support\ViewErrorBag();

        $errors->put(
            'default',
            new \Illuminate\Support\MessageBag([
                'Não foi possível atualizar este tenant.',
            ])
        );

        $html = $this->blade(
            '<x-platform.error-state :errors="$errors" />',
            [
                'errors' => $errors,
            ]
        );

        $html
            ->assertSee(
                __('platform.error_state_title')
            )
            ->assertSee(
                'Não foi possível atualizar este tenant.'
            )
            ->assertSee(
                'role="alert"',
                false
            );
    }}
