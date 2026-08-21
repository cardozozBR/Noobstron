<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Jobs\SendEmailMessageJob;
use App\Mail\TenantEmailMessageMail;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\EmailMessageService;
use App\Services\EmailQueueService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class EmailQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_email_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $tenant = $this->tenant(
            'email-queue-dispatch'
        );

        $message = $this->message();

        app(
            EmailQueueService::class
        )->dispatch(
            $message
        );

        Queue::assertPushed(
            SendEmailMessageJob::class,
            function (
                SendEmailMessageJob $job
            ) use (
                $tenant,
                $message
            ): bool {
                return $job->tenantId ===
                    $tenant->id
                    &&
                    $job->emailMessageId ===
                    $message->id;
            }
        );
    }

    public function test_failed_email_can_be_dispatched_for_retry(): void
    {
        Queue::fake();

        $this->tenant(
            'email-queue-failed'
        );

        $message = app(
            EmailMessageService::class
        )->markFailed(
            $this->message(),
            'Temporary provider failure'
        );

        app(
            EmailQueueService::class
        )->dispatch(
            $message
        );

        Queue::assertPushed(
            SendEmailMessageJob::class,
            1
        );
    }

    public function test_sent_email_cannot_be_dispatched_again(): void
    {
        Queue::fake();

        $this->tenant(
            'email-queue-sent'
        );

        $message = app(
            EmailMessageService::class
        )->markSent(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailQueueService::class
        )->dispatch(
            $message
        );
    }

    public function test_other_tenant_email_cannot_be_dispatched(): void
    {
        Queue::fake();

        $this->tenant(
            'email-queue-tenant-a'
        );

        $message = $this->message();

        $this->tenant(
            'email-queue-tenant-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailQueueService::class
        )->dispatch(
            $message
        );
    }

    public function test_job_restores_tenant_context_and_sends_pending_message(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'email-job-tenant'
        );

        $message = $this->message();

        app(
            TenantContext::class
        )->clear();

        $job = new SendEmailMessageJob(
            $tenant->id,
            $message->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::SENT,
            $message->status
        );

        $this->assertNotNull(
            $message->sent_at
        );

        $this->assertSame(
            $tenant->id,
            app(
                TenantContext::class
            )->get()->id
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            1
        );
    }

    public function test_job_retries_failed_message(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'email-job-retry'
        );

        $message = app(
            EmailMessageService::class
        )->markFailed(
            $this->message(),
            'Temporary error'
        );

        app(
            TenantContext::class
        )->clear();

        $job = new SendEmailMessageJob(
            $tenant->id,
            $message->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::SENT,
            $message->status
        );

        $this->assertNotNull(
            $message->sent_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_reason
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            1
        );
    }

    public function test_job_is_idempotent_when_message_is_already_sent(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'email-job-idempotent'
        );

        $message = app(
            EmailMessageService::class
        )->markSent(
            $this->message()
        );

        app(
            TenantContext::class
        )->clear();

        $job = new SendEmailMessageJob(
            $tenant->id,
            $message->id
        );

        app()->call([
            $job,
            'handle',
        ]);

        $this->assertSame(
            EmailMessageStatus::SENT,
            $message->refresh()->status
        );

        Mail::assertNothingSent();
    }

    public function test_job_cannot_access_message_from_different_tenant(): void
    {
        Mail::fake();

        $tenantA = $this->tenant(
            'email-job-isolation-a'
        );

        $message = $this->message();

        $tenantB = $this->tenant(
            'email-job-isolation-b'
        );

        $job = new SendEmailMessageJob(
            $tenantB->id,
            $message->id
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app()->call([
            $job,
            'handle',
        ]);

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    public function test_job_has_retry_configuration(): void
    {
        $job = new SendEmailMessageJob(
            1,
            1
        );

        $this->assertSame(
            3,
            $job->tries
        );

        $this->assertSame(
            60,
            $job->timeout
        );

        $this->assertSame(
            [
                60,
                300,
            ],
            $job->backoff()
        );
    }

    private function message(): EmailMessage
    {
        return EmailMessage::query()
            ->create([
                'to_email' =>
                    'cliente@example.com',

                'to_name' =>
                    'Cliente',

                'subject' =>
                    'Mensagem em fila',

                'body' =>
                    'Esta mensagem será processada de forma assíncrona.',
            ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' =>
                    'Tenant ' . $slug,

                'slug' =>
                    $slug,

                'status' =>
                    'active',

                'country_code' =>
                    'BR',

                'locale' =>
                    'pt-BR',

                'timezone' =>
                    'America/Fortaleza',

                'currency' =>
                    'BRL',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }
}