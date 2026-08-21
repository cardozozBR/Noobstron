<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Mail\TenantEmailMessageMail;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\EmailDeliveryService;
use App\Services\EmailMessageService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class EmailDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_email_message_can_be_sent(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-send'
        );

        $message = $this->message();

        $result = app(
            EmailDeliveryService::class
        )->send(
            $message
        );

        $this->assertSame(
            EmailMessageStatus::SENT,
            $result->status
        );

        $this->assertNotNull(
            $result->sent_at
        );

        $this->assertNull(
            $result->failed_at
        );

        $this->assertNull(
            $result->failure_reason
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            function (
                TenantEmailMessageMail $mail
            ) use ($message): bool {
                return $mail->hasTo(
                    $message->to_email
                )
                    && $mail->emailMessage->is(
                        $message
                    );
            }
        );
    }

    public function test_sent_email_uses_subject_and_body(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-content'
        );

        $message = EmailMessage::query()
            ->create([
                'to_email' =>
                    'cliente@example.com',

                'to_name' =>
                    'Maria',

                'subject' =>
                    'Sua proposta está pronta',

                'body' =>
                    'Olá Maria, sua proposta está disponível.',
            ]);

        app(
            EmailDeliveryService::class
        )->send(
            $message
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            function (
                TenantEmailMessageMail $mail
            ): bool {
                return $mail->emailMessage->subject ===
                    'Sua proposta está pronta'
                    &&
                    $mail->emailMessage->body ===
                    'Olá Maria, sua proposta está disponível.';
            }
        );
    }

    public function test_sent_email_preserves_recipient_name(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-recipient-name'
        );

        $message = EmailMessage::query()
            ->create([
                'to_email' =>
                    'maria@example.com',

                'to_name' =>
                    'Maria Cliente',

                'subject' =>
                    'Contato',

                'body' =>
                    'Olá Maria.',
            ]);

        app(
            EmailDeliveryService::class
        )->send(
            $message
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            function (
                TenantEmailMessageMail $mail
            ): bool {
                return $mail->hasTo(
                    'maria@example.com',
                    'Maria Cliente'
                );
            }
        );
    }

    public function test_sent_message_cannot_be_sent_again(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-double'
        );

        $service = app(
            EmailDeliveryService::class
        );

        $message = $service->send(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->send(
            $message
        );
    }

    public function test_failed_message_cannot_be_sent_directly(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-failed-direct'
        );

        $message = app(
            EmailMessageService::class
        )->markFailed(
            $this->message(),
            'Temporary failure'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailDeliveryService::class
        )->send(
            $message
        );
    }

    public function test_failed_email_message_can_be_retried_and_sent(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-retry'
        );

        $message = app(
            EmailMessageService::class
        )->markFailed(
            $this->message(),
            'Temporary failure'
        );

        $result = app(
            EmailDeliveryService::class
        )->retry(
            $message
        );

        $this->assertSame(
            EmailMessageStatus::SENT,
            $result->status
        );

        $this->assertNotNull(
            $result->sent_at
        );

        $this->assertNull(
            $result->failed_at
        );

        $this->assertNull(
            $result->failure_reason
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            1
        );
    }

    public function test_pending_email_cannot_use_retry(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-pending-retry'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailDeliveryService::class
        )->retry(
            $this->message()
        );
    }

    public function test_sent_email_cannot_use_retry(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-sent-retry'
        );

        $service = app(
            EmailDeliveryService::class
        );

        $message = $service->send(
            $this->message()
        );

        $this->expectException(
            RuntimeException::class
        );

        $service->retry(
            $message
        );
    }

    public function test_other_tenant_email_cannot_be_sent(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-tenant-a'
        );

        $message = $this->message();

        $this->tenant(
            'email-delivery-tenant-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailDeliveryService::class
        )->send(
            $message
        );
    }

    public function test_other_tenant_failed_email_cannot_be_retried(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-retry-a'
        );

        $message = app(
            EmailMessageService::class
        )->markFailed(
            $this->message(),
            'Temporary failure'
        );

        $this->tenant(
            'email-delivery-retry-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailDeliveryService::class
        )->retry(
            $message
        );
    }

    public function test_fake_delivery_does_not_send_real_email(): void
    {
        Mail::fake();

        $this->tenant(
            'email-delivery-fake'
        );

        $message = $this->message();

        app(
            EmailDeliveryService::class
        )->send(
            $message
        );

        Mail::assertSent(
            TenantEmailMessageMail::class,
            1
        );

        $this->assertSame(
            EmailMessageStatus::SENT,
            $message->refresh()->status
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
                    'Mensagem comercial',

                'body' =>
                    'Olá, temos uma novidade para você.',
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