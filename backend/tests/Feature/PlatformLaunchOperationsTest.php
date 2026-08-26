<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\WhatsAppMessageStatus;
use App\Jobs\SendEmailMessageJob;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\CommercialContact;
use App\Models\EmailMessage;
use App\Models\PaymentEventReceipt;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformLaunchOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_retry_failed_email_message(): void
    {
        Queue::fake();

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Retry Email Tenant',
            'slug' => 'retry-email-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        $message = EmailMessage::query()->create([
            'tenant_id' => $tenant->id,
            'to_email' => 'retry-email@example.test',
            'to_name' => 'Retry Email',
            'subject' => 'Retry de e-mail',
            'body' => 'Mensagem de teste para retry.',
            'status' => EmailMessageStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => 'Temporary delivery failure.',
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.email-failures.retry',
                    $message->id
                )
            );

        $response
            ->assertRedirect(
                route('platform.email-failures')
            )
            ->assertSessionHas(
                'success',
                'E-mail enviado para reprocessamento.'
            );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $message->status
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_reason
        );

        Queue::assertPushed(
            SendEmailMessageJob::class,
            function (
                SendEmailMessageJob $job
            ) use ($tenant, $message): bool {
                return $job->tenantId === $tenant->id
                    && $job->emailMessageId === $message->id;
            }
        );
        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => 'email.retried',
            ]
        );
        $this->assertDatabaseHas(
            'platform_admin_audit_logs',
            [
                'platform_admin_id' => $admin->id,
                'tenant_id' => $tenant->id,
                'action' => 'email.reprocessed',
                'entity_type' => EmailMessage::class,
                'entity_id' => (string) $message->id,
                'result' => 'success',
            ]
        );
    }

    public function test_platform_admin_cannot_retry_non_failed_email_message(): void
    {
        Queue::fake();

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Retry Guard Tenant',
            'slug' => 'retry-guard-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        $message = EmailMessage::query()->create([
            'tenant_id' => $tenant->id,
            'to_email' => 'already-pending@example.test',
            'to_name' => 'Pending Email',
            'subject' => 'Pending email',
            'body' => 'Mensagem já pendente.',
            'status' => EmailMessageStatus::PENDING,
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.email-failures.retry',
                    $message->id
                )
            );

        $response
            ->assertRedirect(
                route('platform.email-failures')
            )
            ->assertSessionHas(
                'error'
            );

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $message->refresh()->status
        );

        Queue::assertNothingPushed();
    }

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

    public function test_platform_dashboard_shows_queue_status_counts(): void
    {
        $admin = $this->platformAdmin();

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test queue failure.',
            'failed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get('http://localhost/platform');

        $response
            ->assertOk()
            ->assertSee('Jobs pendentes')
            ->assertSee('Jobs falhos')
            ->assertSee('Fila aguardando processamento')
            ->assertSee('Falhas na fila precisam de atenção')
            ->assertSee(
                'class="metric-card metric-card--alert"',
                false
            );

        $this->assertSame(
            2,
            substr_count(
                $response->getContent(),
                route('platform.jobs')
            )
        );
    }

    public function test_platform_admin_can_retry_failed_queue_job(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Failed Job Retry Tenant',
            'slug' => 'failed-job-retry-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $message = EmailMessage::query()->create([
            'tenant_id' => $tenant->id,
            'to_email' => 'queue-retry@example.test',
            'subject' => 'Queue retry',
            'body' => 'Mensagem usada para gerar payload real.',
            'status' => EmailMessageStatus::PENDING,
        ]);

        app(TenantContext::class)->clear();

        config([
            'queue.default' => 'database',
        ]);

        Queue::connection('database')->push(
            new SendEmailMessageJob(
                $tenant->id,
                $message->id
            )
        );

        $queuedJob = DB::table('jobs')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull(
            $queuedJob
        );

        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => $queuedJob->queue,
            'payload' => $queuedJob->payload,
            'exception' => 'Test failed queue job.',
            'failed_at' => now(),
        ]);

        DB::table('jobs')
            ->where('id', $queuedJob->id)
            ->delete();

        $this->assertSame(
            0,
            DB::table('jobs')->count()
        );

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.jobs.failed.retry',
                    $uuid
                )
            );

        $response
            ->assertRedirect(
                route('platform.jobs')
            )
            ->assertSessionHas(
                'success',
                'Job enviado para reprocessamento.'
            );

        $this->assertDatabaseMissing(
            'failed_jobs',
            [
                'uuid' => $uuid,
            ]
        );

        $this->assertSame(
            1,
            DB::table('jobs')->count()
        );

        $retriedJob = DB::table('jobs')
            ->first();

        $payload = json_decode(
            $retriedJob->payload,
            true
        );

        $this->assertSame(
            SendEmailMessageJob::class,
            data_get(
                $payload,
                'displayName'
            )
        );
    }

    public function test_platform_admin_can_forget_failed_queue_job(): void
    {
        $admin = $this->platformAdmin();

        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendEmailMessageJob',
            ]),
            'exception' => 'Test failed job to forget.',
            'failed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->delete(
                route(
                    'platform.jobs.failed.forget',
                    $uuid
                )
            );

        $response
            ->assertRedirect(
                route('platform.jobs')
            )
            ->assertSessionHas(
                'success',
                'Job falho removido.'
            );

        $this->assertDatabaseMissing(
            'failed_jobs',
            [
                'uuid' => $uuid,
            ]
        );
    }

    public function test_platform_admin_cannot_forget_missing_failed_queue_job(): void
    {
        $admin = $this->platformAdmin();

        $missingUuid = (string) Str::uuid();

        $response = $this
            ->actingAs($admin, 'platform')
            ->delete(
                route(
                    'platform.jobs.failed.forget',
                    $missingUuid
                )
            );

        $response
            ->assertRedirect(
                route('platform.jobs')
            )
            ->assertSessionHas(
                'error',
                'O job falho não foi encontrado.'
            );

        $this->assertDatabaseMissing(
            'failed_jobs',
            [
                'uuid' => $missingUuid,
            ]
        );
    }

    public function test_platform_admin_cannot_retry_missing_failed_queue_job(): void
    {
        $admin = $this->platformAdmin();

        $missingUuid = (string) Str::uuid();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.jobs.failed.retry',
                    $missingUuid
                )
            );

        $response
            ->assertRedirect(
                route('platform.jobs')
            )
            ->assertSessionHas(
                'error',
                'O job falho não foi encontrado.'
            );

        $this->assertDatabaseMissing(
            'failed_jobs',
            [
                'uuid' => $missingUuid,
            ]
        );

        $this->assertSame(
            0,
            DB::table('jobs')->count()
        );
    }

    public function test_platform_dashboard_shows_message_failure_counts(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Message Failure Tenant',
            'slug' => 'message-failure-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        EmailMessage::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'to_email' => 'failed@example.test',
                'subject' => 'Falha de teste',
                'body' => 'Mensagem de teste.',
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => 'Test email failure.',
            ]);

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'phone' => '5511999999999',
                'body' => 'Mensagem de teste.',
                'status' => 'failed',
                'direction' => 'outbound',
                'provider' => 'test',
                'failed_at' => now(),
                'failure_reason' => 'Test WhatsApp failure.',
            ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform')
            ->assertOk()
            ->assertSee('E-mails falhos')
            ->assertSee('WhatsApps falhos')
            ->assertSee('Falhas de envio de e-mail precisam de atenção')
            ->assertSee('Falhas de envio do WhatsApp precisam de atenção')
            ->assertSee(
                'class="metric-card metric-card--alert"',
                false
            )
            ->assertSee(
                route('platform.email-failures')
            )
            ->assertSee(
                route('platform.whatsapp-failures')
            );
    }

    public function test_platform_admin_can_view_global_whatsapp_failures(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'WhatsApp Failure Tenant',
            'slug' => 'whatsapp-failure-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        WhatsAppMessage::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'phone' => '5511999999999',
                'recipient_name' => 'Cliente Teste',
                'body' => 'Mensagem que falhou.',
                'status' => 'failed',
                'direction' => 'outbound',
                'provider' => 'test',
                'failed_at' => now(),
                'failure_reason' => 'Provider indisponível.',
            ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/whatsapp-failures')
            ->assertOk()
            ->assertSee('Falhas de WhatsApp')
            ->assertSee('WhatsApp Failure Tenant')
            ->assertSee('Cliente Teste')
            ->assertSee('5511999999999')
            ->assertSee('Provider indisponível.');
    }

    public function test_platform_admin_can_filter_whatsapp_failures_by_tenant(): void
{
    $admin = $this->platformAdmin();

    $tenantA = Tenant::query()->create([
        'name' => 'WhatsApp Tenant A',
        'slug' => 'whatsapp-tenant-a',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    $tenantB = Tenant::query()->create([
        'name' => 'WhatsApp Tenant B',
        'slug' => 'whatsapp-tenant-b',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    app(TenantContext::class)->set($tenantA);
    WhatsAppMessage::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantA->id,
            'phone' => '5511991111111',
            'recipient_name' => 'Cliente WhatsApp A',
            'body' => 'Mensagem WhatsApp A.',
            'status' => 'failed',
            'direction' => 'outbound',
            'provider' => 'test',
            'failed_at' => now(),
            'failure_reason' => 'Provider A.',
        ]);

    app(TenantContext::class)->set($tenantB);
    WhatsAppMessage::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantB->id,
            'phone' => '5511992222222',
            'recipient_name' => 'Cliente WhatsApp B',
            'body' => 'Mensagem WhatsApp B.',
            'status' => 'failed',
            'direction' => 'outbound',
            'provider' => 'test',
            'failed_at' => now(),
            'failure_reason' => 'Provider B.',
        ]);

    $this->actingAs($admin, 'platform')
        ->get(
            'http://localhost/platform/whatsapp-failures?tenant_id='
            .$tenantA->id
        )
        ->assertOk()
        ->assertSee('WhatsApp Tenant A')
        ->assertSee('Cliente WhatsApp A')
        ->assertSee('5511991111111')
        ->assertDontSee('WhatsApp Tenant B')
        ->assertDontSee('Cliente WhatsApp B')
        ->assertDontSee('5511992222222')
        ->assertSee('Voltar ao tenant');
}

    public function test_platform_admin_can_retry_failed_whatsapp_message(): void
    {
        Queue::fake();

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Retry WhatsApp Tenant',
            'slug' => 'retry-whatsapp-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $message = WhatsAppMessage::query()->create([
            'tenant_id' => $tenant->id,
            'phone' => '5511999999999',
            'recipient_name' => 'Retry WhatsApp',
            'body' => 'Mensagem de teste para retry.',
            'status' => WhatsAppMessageStatus::FAILED,
            'direction' => 'outbound',
            'provider' => 'test',
            'provider_message_id' => 'old-provider-id',
            'failed_at' => now(),
            'failure_reason' => 'Temporary provider failure.',
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.whatsapp-failures.retry',
                    $message->id
                )
            );

        $response
            ->assertRedirect(
                route('platform.whatsapp-failures')
            )
            ->assertSessionHas(
                'success',
                'Mensagem WhatsApp enviada para reprocessamento.'
            );

        $message->refresh();

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );

        $this->assertSame(
            'test',
            $message->provider
        );

        $this->assertNull(
            $message->provider_message_id
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_reason
        );

        Queue::assertPushed(
            SendWhatsAppMessageJob::class,
            function (
                SendWhatsAppMessageJob $job
            ) use (
                $tenant,
                $message
            ): bool {
                return $job->tenantId === $tenant->id
                    && $job->messageId === $message->id
                    && $job->provider === 'test';
            }
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => 'whatsapp.retried',
            ]
        );
        $this->assertDatabaseHas(
            'platform_admin_audit_logs',
            [
                'platform_admin_id' => $admin->id,
                'tenant_id' => $tenant->id,
                'action' => 'whatsapp.reprocessed',
                'entity_type' => WhatsAppMessage::class,
                'entity_id' => (string) $message->id,
                'result' => 'success',
            ]
        );
    }

    public function test_platform_admin_cannot_retry_non_failed_whatsapp_message(): void
    {
        Queue::fake();

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'WhatsApp Retry Guard Tenant',
            'slug' => 'whatsapp-retry-guard-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $message = WhatsAppMessage::query()->create([
            'tenant_id' => $tenant->id,
            'phone' => '5511888888888',
            'recipient_name' => 'Pending WhatsApp',
            'body' => 'Mensagem ainda pendente.',
            'status' => WhatsAppMessageStatus::PENDING,
            'direction' => 'outbound',
            'provider' => 'test',
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.whatsapp-failures.retry',
                    $message->id
                )
            );

        $response
            ->assertRedirect(
                route('platform.whatsapp-failures')
            )
            ->assertSessionHas(
                'error'
            );

        $message->refresh();

        $this->assertSame(
            WhatsAppMessageStatus::PENDING,
            $message->status
        );

        Queue::assertNotPushed(
            SendWhatsAppMessageJob::class
        );

        $this->assertDatabaseMissing(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'whatsapp.retried',
            ]
        );
    }

    public function test_platform_admin_cannot_retry_failed_whatsapp_message_without_provider(): void
    {
        Queue::fake();

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'WhatsApp Missing Provider Tenant',
            'slug' => 'whatsapp-missing-provider-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $message = WhatsAppMessage::query()->create([
            'tenant_id' => $tenant->id,
            'phone' => '5511777777777',
            'recipient_name' => 'Legacy WhatsApp',
            'body' => 'Mensagem antiga sem provider.',
            'status' => WhatsAppMessageStatus::FAILED,
            'direction' => 'outbound',
            'provider' => null,
            'failed_at' => now(),
            'failure_reason' => 'Legacy provider failure.',
        ]);

        app(TenantContext::class)->clear();

        $response = $this
            ->actingAs($admin, 'platform')
            ->post(
                route(
                    'platform.whatsapp-failures.retry',
                    $message->id
                )
            );

        $response
            ->assertRedirect(
                route('platform.whatsapp-failures')
            )
            ->assertSessionHas(
                'error'
            );

        $message->refresh();

        $this->assertSame(
            WhatsAppMessageStatus::FAILED,
            $message->status
        );

        $this->assertNull(
            $message->provider
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertSame(
            'Legacy provider failure.',
            $message->failure_reason
        );

        Queue::assertNotPushed(
            SendWhatsAppMessageJob::class
        );

        $this->assertDatabaseMissing(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'whatsapp.retried',
            ]
        );
    }

    public function test_platform_admin_can_view_queue_jobs(): void
    {
        $admin = $this->platformAdmin();

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendEmailMessageJob',
            ]),
            'attempts' => 1,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendWhatsAppMessageJob',
            ]),
            'exception' => 'Sensitive exception details.',
            'failed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.jobs')
            );

        $response
            ->assertOk()
            ->assertSee('Filas e jobs')
            ->assertSee('Jobs pendentes')
            ->assertSee('Jobs falhos')
            ->assertSee('App\\Jobs\\SendEmailMessageJob')
            ->assertSee('App\\Jobs\\SendWhatsAppMessageJob')
            ->assertDontSee(
                'Sensitive exception details.'
            );
    }

    public function test_platform_jobs_show_retry_for_failed_job(): void
    {
        $admin = $this->platformAdmin();

        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendEmailMessageJob',
            ]),
            'exception' => 'Test failed job.',
            'failed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.jobs')
            );

        $response
            ->assertOk()
            ->assertSee('Reprocessar')
            ->assertSee(
                route(
                    'platform.jobs.failed.retry',
                    $uuid
                )
            );
    }

    public function test_platform_jobs_show_forget_for_failed_job(): void
    {
        $admin = $this->platformAdmin();

        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendEmailMessageJob',
            ]),
            'exception' => 'Test failed job.',
            'failed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.jobs')
            );

        $response
            ->assertOk()
            ->assertSee('Reprocessar')
            ->assertSee('Remover')
            ->assertSee(
                route(
                    'platform.jobs.failed.retry',
                    $uuid
                )
            )
            ->assertSee(
                route(
                    'platform.jobs.failed.forget',
                    $uuid
                )
            )
            ->assertSee(
                'ATENÇÃO: este job falho será removido definitivamente da lista e não será reprocessado. Deseja continuar?'
            );
    }

    public function test_platform_jobs_page_shows_empty_states(): void
    {
        $admin = $this->platformAdmin();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.jobs')
            )
            ->assertOk()
            ->assertSee('Nenhum job pendente.')
            ->assertSee('Nenhum job falho.');
    }

    public function test_platform_admin_can_view_global_email_failures(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Email Failure Tenant',
            'slug' => 'email-failure-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        EmailMessage::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'to_email' => 'failure@example.test',
                'subject' => 'Falha operacional',
                'body' => 'Mensagem que falhou.',
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => 'SMTP indisponível.',
            ]);

        $this->actingAs($admin, 'platform')
            ->get('http://localhost/platform/email-failures')
            ->assertOk()
            ->assertSee('Falhas de e-mail')
            ->assertSee('Email Failure Tenant')
            ->assertSee('failure@example.test')
            ->assertSee('Falha operacional')
            ->assertSee('SMTP indisponível.');
    }

public function test_platform_admin_can_filter_email_failures_by_tenant(): void
{
    $admin = $this->platformAdmin();

    $tenantA = Tenant::query()->create([
        'name' => 'Email Tenant A',
        'slug' => 'email-tenant-a',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    $tenantB = Tenant::query()->create([
        'name' => 'Email Tenant B',
        'slug' => 'email-tenant-b',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    EmailMessage::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantA->id,
            'to_email' => 'tenant-a@example.test',
            'subject' => 'Falha Tenant A',
            'body' => 'Mensagem A.',
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => 'SMTP A.',
        ]);

    EmailMessage::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantB->id,
            'to_email' => 'tenant-b@example.test',
            'subject' => 'Falha Tenant B',
            'body' => 'Mensagem B.',
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => 'SMTP B.',
        ]);

    $this->actingAs($admin, 'platform')
        ->get(
            'http://localhost/platform/email-failures?tenant_id='
            .$tenantA->id
        )
        ->assertOk()
        ->assertSee('Email Tenant A')
        ->assertSee('tenant-a@example.test')
        ->assertSee('Falha Tenant A')
        ->assertDontSee('Email Tenant B')
        ->assertDontSee('tenant-b@example.test')
        ->assertDontSee('Falha Tenant B')
        ->assertSee('Voltar ao tenant');
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

    public function test_platform_admin_can_filter_payment_webhooks_by_tenant(): void
{
    $admin = $this->platformAdmin();

    $tenantA = Tenant::query()->create([
        'name' => 'Webhook Tenant A',
        'slug' => 'webhook-tenant-a',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    $tenantB = Tenant::query()->create([
        'name' => 'Webhook Tenant B',
        'slug' => 'webhook-tenant-b',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    $plan = Plan::query()->create([
        'code' => 'webhook-filter-plan',
        'name' => 'Webhook Filter Plan',
        'active' => true,
    ]);

    Subscription::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' => 'sub-webhook-filter-a',
            'currency' => 'BRL',
            'amount_minor' => 9900,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

    Subscription::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'payment_provider' => 'stripe',
            'external_reference' => 'sub-webhook-filter-b',
            'currency' => 'BRL',
            'amount_minor' => 24900,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

    PaymentEventReceipt::query()->create([
        'provider' => 'stripe',
        'event_id' => 'evt-webhook-filter-a',
        'event_type' => 'invoice.payment_failed',
        'external_reference' => 'sub-webhook-filter-a',
        'status' => 'failed',
        'attempts' => 1,
        'last_error' => 'WEBHOOK-ERROR-A',
        'processed_at' => null,
    ]);

    PaymentEventReceipt::query()->create([
        'provider' => 'stripe',
        'event_id' => 'evt-webhook-filter-b',
        'event_type' => 'invoice.payment_failed',
        'external_reference' => 'sub-webhook-filter-b',
        'status' => 'failed',
        'attempts' => 1,
        'last_error' => 'WEBHOOK-ERROR-B',
        'processed_at' => null,
    ]);

    app(TenantContext::class)->clear();

    $response = $this
        ->actingAs($admin, 'platform')
        ->get(
            'http://localhost/platform/webhooks'
            .'?tenant_id='.$tenantA->id
            .'&status=failed'
        );

    $response
        ->assertOk()
        ->assertSee('Webhook Tenant A')
        ->assertSee('evt-webhook-filter-a')
        ->assertSee('sub-webhook-filter-a')
        ->assertSee('WEBHOOK-ERROR-A')
        ->assertDontSee('Webhook Tenant B')
        ->assertDontSee('evt-webhook-filter-b')
        ->assertDontSee('sub-webhook-filter-b')
        ->assertDontSee('WEBHOOK-ERROR-B')
        ->assertSee('Voltar ao tenant');

    $response->assertSee(
    e(
        route(
            'platform.webhooks',
            [
                'tenant_id' => $tenantA->id,
                'status' => 'processed',
            ]
        )
    ),
    false
);

$response->assertSee(
    e(
        route(
            'platform.webhooks',
            [
                'tenant_id' => $tenantA->id,
                'status' => 'processing',
            ]
        )
    ),
    false
);

$response->assertSee(
    e(
        route(
            'platform.webhooks',
            [
                'tenant_id' => $tenantA->id,
                'status' => 'failed',
            ]
        )
    ),
    false
);
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
        $this->assertDatabaseHas(
            'platform_admin_audit_logs',
            [
                'platform_admin_id' => $admin->id,
                'tenant_id' => $tenant->id,
                'action' => 'webhook.reprocessed',
                'entity_type' => PaymentEventReceipt::class,
                'entity_id' => (string) $receipt->id,
                'result' => 'success',
            ]
        );

        $log = PlatformAdminAuditLog::query()
            ->where('action', 'webhook.reprocessed')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            [
                'status' => 'processed',
                'attempts' => 2,
                'event_id' => 'evt_platform_retry_123',
                'event_type' => 'customer.subscription.updated',
                'provider' => 'stripe',
            ],
            $log->after_state
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

    public function test_platform_health_exposes_stripe_whatsapp_and_check_timestamp(): void
    {
        $admin = $this->platformAdmin();

        $tenantA = Tenant::query()->create([
            'name' => 'Health WhatsApp Tenant A',
            'slug' => 'health-whatsapp-tenant-a',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Health WhatsApp Tenant B',
            'slug' => 'health-whatsapp-tenant-b',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        DB::table('whatsapp_provider_configs')->insert([
            [
                'tenant_id' => $tenantA->id,
                'provider' => 'meta',
                'sender_id' => 'sender-a',
                'settings' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantA->id,
                'provider' => 'secondary',
                'sender_id' => 'sender-a-secondary',
                'settings' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantB->id,
                'provider' => 'meta',
                'sender_id' => 'sender-b',
                'settings' => null,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        config([
            'services.stripe.secret_key' => 'sk_test_platform_health',
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'checks',
                function (
                    array $checks
                ): bool {
                    return
                        $checks['stripe_configured'] === true
                        && $checks['whatsapp_configured_tenants'] === 1
                        && $checks['checked_at'] !== null;
                }
            );
    }

    public function test_platform_health_shows_advanced_operational_statuses(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Health UI Tenant',
            'slug' => 'health-ui-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        DB::table('whatsapp_provider_configs')->insert([
            'tenant_id' => $tenant->id,
            'provider' => 'meta',
            'sender_id' => 'health-ui-sender',
            'settings' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'services.stripe.secret_key' => 'sk_test_health_ui',
        ]);

        $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            )
            ->assertOk()
            ->assertSee('Stripe')
            ->assertSee('WhatsApp')
            ->assertSee('Verificado em')
            ->assertSee('Credencial principal configurada.')
            ->assertSee('1')
            ->assertSee('tenant(s) com provider ativo.')
            ->assertSee('OK');
    }

    public function test_platform_health_shows_scheduler_health(): void
    {
        $admin = $this->platformAdmin();

        cache()->put(
            'platform.scheduler.last_run_at',
            now()->toIso8601String(),
            now()->addHours(2)
        );

        $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            )
            ->assertOk()
            ->assertSee('Scheduler')
            ->assertSee('Última execução:')
            ->assertSee('OK');
    }

    public function test_platform_health_marks_stale_scheduler_as_critical(): void
    {
        $admin = $this->platformAdmin();

        cache()->put(
            'platform.scheduler.last_run_at',
            now()
                ->subMinutes(10)
                ->toIso8601String(),
            now()->addHours(2)
        );

        $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            )
            ->assertOk()
            ->assertSee('Scheduler')
            ->assertSee('Crítico');
    }

    public function test_platform_health_shows_worker_health(): void
    {
        $admin = $this->platformAdmin();

        cache()->put(
            'platform.worker.last_seen_at',
            now()->toIso8601String(),
            now()->addHours(2)
        );

        $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            )
            ->assertOk()
            ->assertSee('Worker')
            ->assertSee('Última atividade:')
            ->assertSee('OK');
    }

    public function test_platform_health_marks_stale_worker_as_critical(): void
    {
        $admin = $this->platformAdmin();

        cache()->put(
            'platform.worker.last_seen_at',
            now()
                ->subMinutes(10)
                ->toIso8601String(),
            now()->addHours(2)
        );

        $this
            ->actingAs(
                $admin,
                'platform'
            )
            ->get(
                route('platform.health')
            )
            ->assertOk()
            ->assertSee('Worker')
            ->assertSee('Crítico');
    }

    public function test_platform_email_failures_redact_sensitive_data(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Sensitive Failure Tenant',
            'slug' => 'sensitive-failure-tenant',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        EmailMessage::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'to_email' => 'sensitive@example.test',
                'subject' => 'Falha sensível',
                'body' => 'Mensagem.',
                'status' => EmailMessageStatus::FAILED,
                'failed_at' => now(),
                'failure_reason' =>
                    'Authorization: Bearer SUPER_SECRET_VALUE',
            ]);

        app(TenantContext::class)->clear();

        $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.email-failures')
            )
            ->assertOk()
            ->assertDontSee('SUPER_SECRET_VALUE')
            ->assertSee('[REDACTED]');
    }

    public function test_platform_whatsapp_failures_redact_sensitive_data(): void
{
    $admin = $this->platformAdmin();

    $tenant = Tenant::query()->create([
        'name' => 'Sensitive WhatsApp Tenant',
        'slug' => 'sensitive-whatsapp-tenant',
        'status' => 'active',
        'currency' => 'BRL',
    ]);

    app(TenantContext::class)->set($tenant);

    WhatsAppMessage::query()
        ->withoutGlobalScopes()
        ->create([
            'tenant_id' => $tenant->id,
            'phone' => '5511999999999',
            'body' => 'Mensagem sensível.',
            'status' => WhatsAppMessageStatus::FAILED,
            'direction' => 'outbound',
            'provider' => 'test',
            'failed_at' => now(),
            'failure_reason' =>
                'Provider error Authorization: Bearer super-secret-token',
        ]);

    app(TenantContext::class)->clear();

    $response = $this
        ->actingAs($admin, 'platform')
        ->get(
            route('platform.whatsapp-failures')
        );

    $response
        ->assertOk()
        ->assertDontSee('super-secret-token')
        ->assertDontSee(
            'Authorization: Bearer super-secret-token'
        );
}

public function test_platform_webhooks_redact_sensitive_data(): void
{
    $admin = $this->platformAdmin();

    PaymentEventReceipt::query()->create([
        'provider' => 'stripe',
        'event_id' => 'evt_sensitive_webhook',
        'event_type' => 'invoice.payment_failed',
        'external_reference' => 'sub_sensitive_webhook',
        'status' => 'failed',
        'attempts' => 1,
        'last_error' =>
            'Stripe error api_key=sk_test_super_secret_value',
        'processed_at' => null,
    ]);

    $response = $this
        ->actingAs($admin, 'platform')
        ->get(
            route(
                'platform.webhooks',
                ['status' => 'failed']
            )
        );

    $response
        ->assertOk()
        ->assertDontSee('sk_test_super_secret_value')
        ->assertDontSee(
            'api_key=sk_test_super_secret_value'
        );
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

    public function test_platform_dashboard_shows_new_subscriptions_from_last_thirty_days(): void
{
    $admin = PlatformAdmin::query()->create([
        'name' => 'Platform Admin',
        'email' => 'new-subscriptions@example.test',
        'password' => Hash::make('SenhaSegura123'),
        'is_active' => true,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Tenant Novas Assinaturas',
        'slug' => 'tenant-novas-assinaturas',
        'status' => 'active',
    ]);

    $plan = Plan::query()->create([
        'code' => 'new-subscriptions-plan',
        'name' => 'Plano Novas Assinaturas',
        'active' => true,
    ]);

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'cancelled',
        'current_period_start' => now()->subMonths(2),
        'current_period_end' => now()->subMonth(),
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ]);

    $this
        ->actingAs($admin, 'platform')
        ->get(
            route('platform.dashboard')
        )
        ->assertOk()
        ->assertSee(
            __('platform.dashboard.new_subscriptions')
        )
        ->assertSee('1');
}

public function test_platform_dashboard_shows_effective_cancellations_from_last_thirty_days(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'cancellations@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Cancelamentos',
            'slug' => 'tenant-cancelamentos',
            'status' => 'active',
        ]);

        $plan = Plan::query()->create([
            'code' => 'cancellations-plan',
            'name' => 'Plano Cancelamentos',
            'active' => true,
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now(),
            'canceled_at' => now()->subDays(5),
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subMonth(),
            'canceled_at' => now()->subDays(40),
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancel_at' => now()->addDays(10),
            'canceled_at' => null,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee(
                __('platform.dashboard.cancellations')
            )
            ->assertSee('1');
    }

    public function test_platform_dashboard_shows_converted_trials(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'trial-conversions@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'code' => 'trial-conversion-plan',
            'name' => 'Plano Conversão Trial',
            'active' => true,
        ]);

        $converted = Tenant::query()->create([
            'name' => 'Trial Convertido',
            'slug' => 'trial-convertido',
            'status' => 'active',
            'trial_started_at' => now()->subDays(20),
            'trial_ends_at' => now()->addDays(10),
        ]);

        Subscription::query()->create([
            'tenant_id' => $converted->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $afterTrial = Tenant::query()->create([
            'name' => 'Assinatura Pós Trial',
            'slug' => 'assinatura-pos-trial',
            'status' => 'active',
            'trial_started_at' => now()->subDays(60),
            'trial_ends_at' => now()->subDays(30),
        ]);

        Subscription::query()->create([
            'tenant_id' => $afterTrial->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        Tenant::query()->create([
            'name' => 'Trial Sem Assinatura',
            'slug' => 'trial-sem-assinatura',
            'status' => 'active',
            'trial_started_at' => now()->subDays(10),
            'trial_ends_at' => now()->addDays(20),
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee(
                __('platform.dashboard.trial_conversions')
            )
            ->assertSee('1');
    }

    public function test_platform_dashboard_shows_expired_trials_without_conversion(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'expired-trials@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'code' => 'expired-trial-plan',
            'name' => 'Plano Trial Expirado',
            'active' => true,
        ]);

        Tenant::query()->create([
            'name' => 'Trial Expirado Sem Conversão',
            'slug' => 'trial-expirado-sem-conversao',
            'status' => 'active',
            'trial_started_at' => now()->subDays(40),
            'trial_ends_at' => now()->subDays(10),
        ]);

        $converted = Tenant::query()->create([
            'name' => 'Trial Expirado Convertido',
            'slug' => 'trial-expirado-convertido',
            'status' => 'active',
            'trial_started_at' => now()->subDays(40),
            'trial_ends_at' => now()->subDays(10),
        ]);

        Subscription::query()->create([
            'tenant_id' => $converted->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        Tenant::query()->create([
            'name' => 'Trial Ainda Ativo',
            'slug' => 'trial-ainda-ativo',
            'status' => 'active',
            'trial_started_at' => now()->subDays(10),
            'trial_ends_at' => now()->addDays(10),
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee(
                __('platform.dashboard.expired_trials_without_conversion')
            )
            ->assertSee('1');
    }

    public function test_platform_dashboard_shows_basic_churn_rate(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'churn@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'code' => 'churn-plan',
            'name' => 'Plano Churn',
            'active' => true,
        ]);

        foreach (range(1, 3) as $index) {
            $tenant = Tenant::query()->create([
                'name' => "Tenant Ativo {$index}",
                'slug' => "tenant-ativo-{$index}",
                'status' => 'active',
            ]);

            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        $cancelledTenant = Tenant::query()->create([
            'name' => 'Tenant Cancelado',
            'slug' => 'tenant-cancelado-churn',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'tenant_id' => $cancelledTenant->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now(),
            'canceled_at' => now()->subDays(5),
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee(
                __('platform.dashboard.churn')
            )
            ->assertSee('25,00%');
    }

    public function test_platform_dashboard_shows_zero_churn_when_base_is_empty(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'zero-churn@example.test',
            'password' => Hash::make('SenhaSegura123'),
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee(
                __('platform.dashboard.churn')
            )
            ->assertSee('0,00%');
    }
}
