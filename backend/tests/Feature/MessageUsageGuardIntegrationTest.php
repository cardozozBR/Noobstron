<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UsageMetric;
use App\Exceptions\UsageBlockedException;
use App\Models\EmailMessage;
use App\Models\Plan;
use App\Models\PlanUsageLimit;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\EmailMessageService;
use App\Services\TenantContext;
use App\Services\TenantUsageService;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageUsageGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_outbound_is_allowed_below_limit(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-email-allowed'
            );

        $this->messageLimit(
            $plan,
            2
        );

        $this->setTenant(
            $tenant
        );

        $message = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'allowed@example.test',
            'subject' =>
                'Allowed',
            'body' =>
                'Allowed email body',
        ]);

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );

        $this->assertSame(
            1,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::MESSAGES
                )
        );
    }

    public function test_email_outbound_is_blocked_when_limit_is_reached(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-email-blocked'
            );

        $this->messageLimit(
            $plan,
            1
        );

        $this->existingEmail(
            $tenant
        );

        $this->setTenant(
            $tenant
        );

        try {
            app(
                EmailMessageService::class
            )->create([
                'to_email' =>
                    'blocked@example.test',
                'subject' =>
                    'Blocked',
                'body' =>
                    'This email must not be created',
            ]);

            $this->fail(
                'Expected UsageBlockedException.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                UsageMetric::MESSAGES,
                $exception->metric
            );

            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );

            $this->assertSame(
                1,
                $exception->used
            );

            $this->assertSame(
                1,
                $exception->requested
            );

            $this->assertSame(
                1,
                $exception->limit
            );

            $this->assertSame(
                0,
                $exception->remaining
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );
        }

        $this->assertSame(
            1,
            EmailMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_whatsapp_outbound_is_allowed_below_limit(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-whatsapp-allowed'
            );

        $this->messageLimit(
            $plan,
            1
        );

        $this->setTenant(
            $tenant
        );

        $message = app(
            WhatsAppMessageService::class
        )->create([
            'phone' =>
                '5511999999999',
            'body' =>
                'Allowed WhatsApp',
        ]);

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );

        $this->assertSame(
            'outbound',
            $message->direction
        );

        $this->assertSame(
            1,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::MESSAGES
                )
        );
    }

    public function test_whatsapp_outbound_shares_quota_with_email(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-shared-quota'
            );

        $this->messageLimit(
            $plan,
            1
        );

        $this->existingEmail(
            $tenant
        );

        $this->setTenant(
            $tenant
        );

        try {
            app(
                WhatsAppMessageService::class
            )->create([
                'phone' =>
                    '5511888888888',
                'body' =>
                    'Must be blocked',
            ]);

            $this->fail(
                'Expected shared message quota blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                UsageMetric::MESSAGES,
                $exception->metric
            );

            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );

            $this->assertTrue(
                $exception->upgradeSuggested
            );
        }

        $this->assertSame(
            0,
            WhatsAppMessage::query()
                ->withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'direction',
                    'outbound'
                )
                ->count()
        );
    }

    public function test_email_outbound_shares_quota_with_whatsapp(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-shared-reverse'
            );

        $this->messageLimit(
            $plan,
            1
        );

        $this->existingWhatsApp(
            $tenant
        );

        $this->setTenant(
            $tenant
        );

        $this->expectException(
            UsageBlockedException::class
        );

        app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'shared@example.test',
            'subject' =>
                'Shared quota',
            'body' =>
                'Must be blocked',
        ]);
    }

    public function test_whatsapp_inbound_is_allowed_even_with_zero_message_limit(): void
    {
        [$tenant, $plan] =
            $this->subscribedTenant(
                'message-guard-inbound'
            );

        $this->messageLimit(
            $plan,
            0
        );

        $this->setTenant(
            $tenant
        );

        $message = app(
            WhatsAppMessageService::class
        )->receive([
            'phone' =>
                '5511777777777',
            'body' =>
                'Inbound customer message',
            'provider' =>
                'test-provider',
            'provider_message_id' =>
                'inbound-001',
        ]);

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );

        $this->assertSame(
            'inbound',
            $message->direction
        );

        $this->assertSame(
            0,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::MESSAGES
                )
        );
    }

    public function test_message_guard_is_isolated_between_tenants(): void
    {
        [$blockedTenant, $blockedPlan] =
            $this->subscribedTenant(
                'message-guard-blocked-tenant'
            );

        [$allowedTenant, $allowedPlan] =
            $this->subscribedTenant(
                'message-guard-allowed-tenant'
            );

        $this->messageLimit(
            $blockedPlan,
            1
        );

        $this->messageLimit(
            $allowedPlan,
            2
        );

        $this->existingEmail(
            $blockedTenant
        );

        $this->setTenant(
            $allowedTenant
        );

        $allowed = app(
            WhatsAppMessageService::class
        )->create([
            'phone' =>
                '5511666666666',
            'body' =>
                'Allowed tenant message',
        ]);

        $this->assertSame(
            $allowedTenant->id,
            $allowed->tenant_id
        );

        $this->setTenant(
            $blockedTenant
        );

        try {
            app(
                WhatsAppMessageService::class
            )->create([
                'phone' =>
                    '5511555555555',
                'body' =>
                    'Blocked tenant message',
            ]);

            $this->fail(
                'Expected tenant isolated blocking.'
            );
        } catch (
            UsageBlockedException $exception
        ) {
            $this->assertSame(
                'limit_exceeded',
                $exception->reason
            );
        }

        $this->assertSame(
            1,
            app(TenantUsageService::class)
                ->value(
                    $blockedTenant,
                    UsageMetric::MESSAGES
                )
        );

        $this->assertSame(
            1,
            app(TenantUsageService::class)
                ->value(
                    $allowedTenant,
                    UsageMetric::MESSAGES
                )
        );
    }

    public function test_legacy_tenant_without_subscription_remains_compatible(): void
    {
        $tenant = $this->tenant(
            'message-guard-legacy'
        );

        $this->setTenant(
            $tenant
        );

        $email = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'legacy@example.test',
            'subject' =>
                'Legacy',
            'body' =>
                'Legacy compatibility',
        ]);

        $whatsApp = app(
            WhatsAppMessageService::class
        )->create([
            'phone' =>
                '5511444444444',
            'body' =>
                'Legacy WhatsApp',
        ]);

        $this->assertSame(
            $tenant->id,
            $email->tenant_id
        );

        $this->assertSame(
            $tenant->id,
            $whatsApp->tenant_id
        );

        $this->assertSame(
            2,
            app(TenantUsageService::class)
                ->value(
                    $tenant,
                    UsageMetric::MESSAGES
                )
        );
    }

    private function subscribedTenant(
        string $slug
    ): array {
        $tenant = $this->tenant(
            $slug
        );

        $plan = Plan::query()->create([
            'code' =>
                $slug . '-plan',
            'name' =>
                ucfirst($slug) . ' Plan',
            'active' =>
                true,
        ]);

        DB::table(
            'subscriptions'
        )->insert([
            'tenant_id' =>
                $tenant->id,
            'plan_id' =>
                $plan->id,
            'status' =>
                SubscriptionStatus::ACTIVE->value,
            'current_period_start' =>
                '2026-08-18 00:00:00',
            'current_period_end' =>
                '2026-09-18 00:00:00',
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
        ]);

        return [
            $tenant,
            $plan,
        ];
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' =>
                ucfirst($slug),
            'slug' =>
                $slug,
            'status' =>
                'active',
        ]);
    }

    private function messageLimit(
        Plan $plan,
        ?int $limit
    ): void {
        PlanUsageLimit::query()->create([
            'plan_id' =>
                $plan->id,
            'metric' =>
                UsageMetric::MESSAGES,
            'limit_value' =>
                $limit,
        ]);
    }

    private function setTenant(
        Tenant $tenant
    ): void {
        app(
            TenantContext::class
        )->set(
            $tenant
        );
    }

    private function existingEmail(
        Tenant $tenant
    ): void {
        EmailMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' =>
                    $tenant->id,
                'to_email' =>
                    'existing@example.test',
                'subject' =>
                    'Existing',
                'body' =>
                    'Existing message',
                'status' =>
                    'pending',
            ]);
    }

    private function existingWhatsApp(
        Tenant $tenant
    ): void {
        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'tenant_id' =>
                    $tenant->id,
                'phone' =>
                    '5511333333333',
                'body' =>
                    'Existing WhatsApp',
                'status' =>
                    'pending',
                'direction' =>
                    'outbound',
            ]);
    }
}