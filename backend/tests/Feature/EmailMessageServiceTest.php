<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\EmailMessageService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class EmailMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_message_can_be_created(): void
    {
        $this->tenant(
            'email-service-create'
        );

        $message = app(
            EmailMessageService::class
        )->create([
            'to_email' =>
                'CLIENTE@EXAMPLE.COM',

            'to_name' =>
                '  Cliente Teste  ',

            'subject' =>
                '  Proposta disponível  ',

            'body' =>
                'Olá, sua proposta está pronta.',
        ]);

        $this->assertSame(
            'cliente@example.com',
            $message->to_email
        );

        $this->assertSame(
            'Cliente Teste',
            $message->to_name
        );

        $this->assertSame(
            'Proposta disponível',
            $message->subject
        );

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $message->status
        );
    }

    public function test_email_message_can_be_marked_as_sent(): void
    {
        $this->tenant(
            'email-service-sent'
        );

        $message = $this->message();

        $sentAt = Carbon::parse(
            '2026-08-17 09:00:00'
        );

        $result = app(
            EmailMessageService::class
        )->markSent(
            $message,
            $sentAt
        );

        $this->assertSame(
            EmailMessageStatus::SENT,
            $result->status
        );

        $this->assertTrue(
            $result->sent_at->equalTo(
                $sentAt
            )
        );

        $this->assertNull(
            $result->failed_at
        );

        $this->assertNull(
            $result->failure_reason
        );
    }

    public function test_email_message_can_be_marked_as_failed(): void
    {
        $this->tenant(
            'email-service-failed'
        );

        $message = $this->message();

        $failedAt = Carbon::parse(
            '2026-08-17 09:05:00'
        );

        $result = app(
            EmailMessageService::class
        )->markFailed(
            $message,
            '  SMTP connection failed  ',
            $failedAt
        );

        $this->assertSame(
            EmailMessageStatus::FAILED,
            $result->status
        );

        $this->assertTrue(
            $result->failed_at->equalTo(
                $failedAt
            )
        );

        $this->assertSame(
            'SMTP connection failed',
            $result->failure_reason
        );

        $this->assertNull(
            $result->sent_at
        );
    }

    public function test_failure_reason_is_required(): void
    {
        $this->tenant(
            'email-service-failed-reason'
        );

        $message = $this->message();

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailMessageService::class
        )->markFailed(
            $message,
            '   '
        );
    }

    public function test_failed_email_message_can_be_retried(): void
    {
        $this->tenant(
            'email-service-retry'
        );

        $message = $this->message();

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markFailed(
            $message,
            'Temporary provider error'
        );

        $result = $service->retry(
            $message
        );

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $result->status
        );

        $this->assertNull(
            $result->sent_at
        );

        $this->assertNull(
            $result->failed_at
        );

        $this->assertNull(
            $result->failure_reason
        );
    }

    public function test_sent_email_message_cannot_be_marked_as_sent_again(): void
    {
        $this->tenant(
            'email-service-double-sent'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markSent(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->markSent(
            $message
        );
    }

    public function test_sent_email_message_cannot_be_marked_as_failed(): void
    {
        $this->tenant(
            'email-service-sent-failed'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markSent(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->markFailed(
            $message,
            'Should not happen'
        );
    }

    public function test_failed_email_message_cannot_be_marked_as_sent_directly(): void
    {
        $this->tenant(
            'email-service-failed-sent'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markFailed(
            $this->message(),
            'Temporary error'
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->markSent(
            $message
        );
    }

    public function test_pending_email_message_cannot_be_retried(): void
    {
        $this->tenant(
            'email-service-pending-retry'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailMessageService::class
        )->retry(
            $this->message()
        );
    }

    public function test_sent_email_message_cannot_be_retried(): void
    {
        $this->tenant(
            'email-service-sent-retry'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markSent(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->retry(
            $message
        );
    }

    public function test_other_tenant_email_message_cannot_be_marked_as_sent(): void
    {
        $tenantA = $this->tenant(
            'email-service-tenant-a'
        );

        $message = $this->message();

        $this->tenant(
            'email-service-tenant-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailMessageService::class
        )->markSent(
            $message
        );

        app(TenantContext::class)->set(
            $tenantA
        );
    }

    public function test_other_tenant_email_message_cannot_be_marked_as_failed(): void
    {
        $this->tenant(
            'email-service-fail-a'
        );

        $message = $this->message();

        $this->tenant(
            'email-service-fail-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailMessageService::class
        )->markFailed(
            $message,
            'Provider failure'
        );
    }

    public function test_other_tenant_email_message_cannot_be_retried(): void
    {
        $this->tenant(
            'email-service-retry-a'
        );

        $service = app(
            EmailMessageService::class
        );

        $message = $service->markFailed(
            $this->message(),
            'Temporary error'
        );

        $this->tenant(
            'email-service-retry-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        $service->retry(
            $message
        );
    }

    public function test_failed_transition_does_not_leave_partial_changes(): void
    {
        $this->tenant(
            'email-service-atomic'
        );

        $message = $this->message();

        try {
            app(
                EmailMessageService::class
            )->markFailed(
                $message,
                '   '
            );
        } catch (RuntimeException) {
            //
        }

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
    }

    private function message(): EmailMessage
    {
        return EmailMessage::query()->create([
            'to_email' =>
                'cliente@example.com',

            'to_name' =>
                'Cliente',

            'subject' =>
                'Mensagem',

            'body' =>
                'Conteúdo da mensagem.',
        ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
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

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}