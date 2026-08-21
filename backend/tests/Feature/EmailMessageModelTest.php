<?php

namespace Tests\Feature;

use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmailMessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_message_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'email-message-tenant'
        );

        $message = $this->message();

        $this->assertSame(
            $tenant->id,
            $message->tenant_id
        );
    }

    public function test_email_message_has_expected_casts(): void
    {
        $this->tenant(
            'email-message-casts'
        );

        $message = $this->message([
            'sent_at' => now(),
            'failed_at' => now(),
        ]);

        $this->assertInstanceOf(
            EmailMessageStatus::class,
            $message->status
        );

        $this->assertNotNull(
            $message->sent_at
        );

        $this->assertNotNull(
            $message->failed_at
        );
    }

    public function test_email_message_defaults_to_pending(): void
    {
        $this->tenant(
            'email-message-pending'
        );

        $message = $this->message();

        $this->assertSame(
            EmailMessageStatus::PENDING,
            $message->status
        );
    }

    public function test_email_message_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'email-message-query-a'
        );

        $messageA = $this->message();

        $this->tenant(
            'email-message-query-b'
        );

        $this->message([
            'to_email' =>
                'other@example.com',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            [$messageA->id],
            EmailMessage::query()
                ->pluck('id')
                ->all()
        );
    }

    public function test_email_message_from_other_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant(
            'email-message-find-a'
        );

        $this->tenant(
            'email-message-find-b'
        );

        $foreign = $this->message();

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            EmailMessage::query()->find(
                $foreign->id
            )
        );
    }

    public function test_recipient_email_is_normalized(): void
    {
        $this->tenant(
            'email-message-recipient'
        );

        $message = $this->message([
            'to_email' =>
                '  CUSTOMER@EXAMPLE.COM  ',
        ]);

        $this->assertSame(
            'customer@example.com',
            $message->to_email
        );
    }

    public function test_subject_is_normalized(): void
    {
        $this->tenant(
            'email-message-subject'
        );

        $message = $this->message([
            'subject' =>
                '  Sua proposta está pronta  ',
        ]);

        $this->assertSame(
            'Sua proposta está pronta',
            $message->subject
        );
    }

    public function test_blank_recipient_is_rejected(): void
    {
        $this->tenant(
            'email-message-blank-recipient'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->message([
            'to_email' => '   ',
        ]);
    }

    public function test_invalid_recipient_is_rejected(): void
    {
        $this->tenant(
            'email-message-invalid-recipient'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->message([
            'to_email' =>
                'not-an-email',
        ]);
    }

    public function test_blank_subject_is_rejected(): void
    {
        $this->tenant(
            'email-message-blank-subject'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->message([
            'subject' => '   ',
        ]);
    }

    public function test_blank_body_is_rejected(): void
    {
        $this->tenant(
            'email-message-blank-body'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->message([
            'body' => '   ',
        ]);
    }

    public function test_sent_fields_are_supported(): void
    {
        $this->tenant(
            'email-message-sent'
        );

        $message = $this->message([
            'status' =>
                EmailMessageStatus::SENT,
            'sent_at' => now(),
        ]);

        $this->assertSame(
            EmailMessageStatus::SENT,
            $message->status
        );

        $this->assertNotNull(
            $message->sent_at
        );
    }

    public function test_failed_fields_are_supported(): void
    {
        $this->tenant(
            'email-message-failed'
        );

        $message = $this->message([
            'status' =>
                EmailMessageStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' =>
                '  Temporary transport failure  ',
        ]);

        $this->assertSame(
            EmailMessageStatus::FAILED,
            $message->status
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertSame(
            'Temporary transport failure',
            $message->failure_reason
        );
    }

    public function test_optional_recipient_name_is_normalized(): void
    {
        $this->tenant(
            'email-message-name'
        );

        $message = $this->message([
            'to_name' =>
                '  Maria Oliveira  ',
        ]);

        $this->assertSame(
            'Maria Oliveira',
            $message->to_name
        );

        $message = $this->message([
            'to_email' =>
                'second@example.com',
            'to_name' => '   ',
        ]);

        $this->assertNull(
            $message->to_name
        );
    }

    public function test_tenant_has_email_messages_relation(): void
    {
        $tenant = $this->tenant(
            'email-message-parent'
        );

        $message = $this->message();

        $this->assertTrue(
            $tenant->emailMessages
                ->contains(
                    $message
                )
        );
    }

    private function message(
        array $override = []
    ): EmailMessage {
        return EmailMessage::query()->create(
            array_merge(
                [
                    'to_email' =>
                        'customer@example.com',
                    'to_name' =>
                        'Cliente Exemplo',
                    'subject' =>
                        'Sua mensagem está pronta',
                    'body' =>
                        'Olá! Esta é uma mensagem de teste.',
                ],
                $override
            )
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}
