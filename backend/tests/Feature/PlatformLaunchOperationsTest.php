<?php

namespace Tests\Feature;

use App\Models\CommercialContact;
use App\Models\PaymentEventReceipt;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformLaunchOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_dashboard_exposes_subscription_trial_revenue_and_usage_overview(): void
    {
        $admin = $this->platformAdmin();

        $plan = Plan::query()->create([
            'code' => 'launch-plan',
            'name' => 'Launch Plan',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 19900,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Launch Tenant',
            'slug' => 'launch-tenant',
            'status' => 'active',
            'currency' => 'BRL',
            'trial_started_at' => now()->subDay(),
            'trial_ends_at' => now()->addDays(4),
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_launch_test',
            'paid_at' => now()->subDay(),
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'currency' => 'BRL',
            'amount_minor' => 19900,
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('Assinaturas ativas')
            ->assertSee('Trials vencendo em 7 dias')
            ->assertSee('MRR contratual')
            ->assertSee('BRL 199,00')
            ->assertSee('Uso global')
            ->assertSee('Webhooks');
    }

    public function test_platform_dashboard_shows_webhook_status_counts(): void
    {
        $admin = $this->platformAdmin();

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_dashboard_failed_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_dashboard_failed_123',
            'status' => 'failed',
            'attempts' => 2,
            'last_error' => 'Dashboard failure.',
            'processed_at' => null,
        ]);

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_dashboard_processing_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_dashboard_processing_123',
            'status' => 'processing',
            'attempts' => 1,
            'last_error' => null,
            'processed_at' => null,
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('Webhooks falhos')
            ->assertSee('Webhooks em processamento')
            ->assertSee('Última falha:')
            ->assertSee(
                'class="metric-card metric-card--alert"',
                false
            )
            ->assertSee('1')
            ->assertSee(
                route(
                    'platform.webhooks',
                    ['status' => 'failed']
                )
            )
            ->assertSee(
                route(
                    'platform.webhooks',
                    ['status' => 'processing']
                )
            );
    }

    public function test_platform_dashboard_shows_no_pending_webhook_failure_message(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('Webhooks falhos')
            ->assertSee('Nenhuma falha pendente')
            ->assertDontSee('Última falha:')
            ->assertDontSee(
                'class="metric-card metric-card--alert"',
                false
            );
    }

    public function test_cancelled_paid_subscription_is_excluded_from_contractual_mrr(): void
    {
        $admin = $this->platformAdmin();

        $plan = Plan::query()->create([
            'code' => 'cancelled-mrr-plan',
            'name' => 'Cancelled MRR Plan',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 49900,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Cancelled MRR Tenant',
            'slug' => 'cancelled-mrr-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_cancelled_mrr_test',
            'paid_at' => now()->subMonth(),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now(),
            'canceled_at' => now(),
            'currency' => 'BRL',
            'amount_minor' => 49900,
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('MRR contratual')
            ->assertDontSee('BRL 499,00');
    }

    public function test_platform_admin_can_view_payment_webhook_receipts(): void
    {
        $admin = $this->platformAdmin();

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_platform_webhook_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_platform_webhook_123',
            'status' => 'processed',
            'attempts' => 1,
            'last_error' => null,
            'processed_at' => now(),
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/webhooks')
            ->assertOk()
            ->assertSee('Webhooks')
            ->assertSee('STRIPE')
            ->assertSee('evt_platform_webhook_123')
            ->assertSee(
                'customer.subscription.updated'
            )
            ->assertSee(
                'sub_platform_webhook_123'
            )
            ->assertSee('PROCESSED')
            ->assertSee('1');
    }

    public function test_platform_admin_can_filter_failed_payment_webhook_receipts(): void
    {
        $admin = $this->platformAdmin();

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_platform_processed_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_platform_processed_123',
            'status' => 'processed',
            'attempts' => 1,
            'last_error' => null,
            'processed_at' => now(),
        ]);

        PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_platform_failed_123',
            'event_type' => 'invoice.payment_failed',
            'external_reference' => 'sub_platform_failed_123',
            'status' => 'failed',
            'attempts' => 2,
            'last_error' => 'Test webhook processing failure.',
            'processed_at' => null,
        ]);

        $this->actingAs($admin, 'platform')
            ->get('/platform/webhooks?status=failed')
            ->assertOk()
            ->assertSee('Falhos')
            ->assertSee('evt_platform_failed_123')
            ->assertSee('invoice.payment_failed')
            ->assertSee('FAILED')
            ->assertSee(
                'Test webhook processing failure.'
            )
            ->assertDontSee(
                'evt_platform_processed_123'
            );
    }

    public function test_platform_webhooks_show_retry_only_for_failed_receipt_with_payload(): void
    {
        $admin = $this->platformAdmin();

        $failedReceipt = PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_retry_visible_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_retry_visible_123',
            'status' => 'failed',
            'attempts' => 2,
            'last_error' => 'Temporary failure.',
            'payload' => [
                'type' => 'customer.subscription.updated',
                'data' => [
                    'object' => [
                        'id' => 'sub_retry_visible_123',
                        'status' => 'active',
                    ],
                ],
            ],
            'processed_at' => null,
        ]);

        $processedReceipt = PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_retry_hidden_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_retry_hidden_123',
            'status' => 'processed',
            'attempts' => 1,
            'last_error' => null,
            'payload' => [
                'type' => 'customer.subscription.updated',
                'data' => [
                    'object' => [
                        'id' => 'sub_retry_hidden_123',
                        'status' => 'active',
                    ],
                ],
            ],
            'processed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get('/platform/webhooks');

        $response
            ->assertOk()
            ->assertSee('evt_retry_visible_123')
            ->assertSee('evt_retry_hidden_123')
            ->assertSee('Reprocessar')
            ->assertSee(
                route(
                    'platform.webhooks.retry',
                    $failedReceipt
                )
            )
            ->assertDontSee(
                route(
                    'platform.webhooks.retry',
                    $processedReceipt
                )
            );
    }

    public function test_platform_admin_can_retry_failed_payment_webhook(): void
    {
        $admin = $this->platformAdmin();

        $plan = Plan::query()->create([
            'code' => 'retry-webhook-plan',
            'name' => 'Retry Webhook Plan',
            'active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Retry Webhook Tenant',
            'slug' => 'retry-webhook-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_provider' => 'stripe',
            'external_reference' => 'sub_platform_retry_123',
            'paid_at' => now()->subDay(),
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $receipt = PaymentEventReceipt::query()->create([
            'provider' => 'stripe',
            'event_id' => 'evt_platform_retry_123',
            'event_type' => 'customer.subscription.updated',
            'external_reference' => 'sub_platform_retry_123',
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'Temporary failure.',
            'payload' => [
                'type' => 'customer.subscription.updated',
                'data' => [
                    'object' => [
                        'id' => 'sub_platform_retry_123',
                        'status' => 'active',
                        'cancel_at' => null,
                        'canceled_at' => null,
                        'cancel_at_period_end' => false,
                    ],
                ],
            ],
            'processed_at' => null,
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                "/platform/webhooks/{$receipt->id}/retry"
            );

        $response
            ->assertRedirect(
                route('platform.webhooks')
            )
            ->assertSessionHas(
                'success',
                'Webhook reprocessado com sucesso.'
            );

        $receipt->refresh();

        $this->assertSame(
            'processed',
            $receipt->status
        );

        $this->assertSame(
            2,
            $receipt->attempts
        );

        $this->assertNull(
            $receipt->last_error
        );

        $this->assertNotNull(
            $receipt->processed_at
        );

        $this->assertSame(
            'active',
            $subscription->refresh()->status->value
        );
    }

    public function test_platform_admin_can_view_operational_health_and_commercial_contacts(): void
    {
        $admin = $this->platformAdmin();

        CommercialContact::query()->create([
            'name' => 'Lead Comercial',
            'email' => 'lead@example.test',
            'message' => 'Quero uma demonstração.',
            'status' => 'new',
        ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/health')
            ->assertOk()
            ->assertSee('Saúde operacional');

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/contacts')
            ->assertOk()
            ->assertSee('Lead Comercial')
            ->assertSee('lead@example.test');
    }

    private function platformAdmin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Launch Master',
            'email' => 'launch-master@example.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }
}
