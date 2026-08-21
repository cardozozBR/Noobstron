<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppProvider;
use App\Enums\WhatsAppMessageStatus;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProviderConfig;
use App\Services\TenantContext;
use App\Services\WhatsAppProviderRegistry;
use App\Support\WhatsAppProviderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendWhatsAppMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_restores_tenant_and_sends_pending_message(): void
    {
        $tenant = $this->tenant(
            'job-send'
        );

        $this->config(
            'meta'
        );

        app(
            WhatsAppProviderRegistry::class
        )->register(
            $this->provider(
                'meta'
            )
        );

        $message = $this->message();

        app(
            TenantContext::class
        )->clear();

        $job = new SendWhatsAppMessageJob(
            $tenant->id,
            $message->id,
            'meta'
        );

        app()->call([
            $job,
            'handle',
        ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $fresh = $message->fresh();

        $this->assertSame(
            WhatsAppMessageStatus::SENT,
            $fresh->status
        );

        $this->assertSame(
            'meta',
            $fresh->provider
        );

        $this->assertSame(
            'job-provider-message',
            $fresh->provider_message_id
        );
    }

    public function test_job_does_not_resend_non_pending_message(): void
    {
        $tenant = $this->tenant(
            'job-idempotent'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem',

                'status' =>
                    WhatsAppMessageStatus::SENT,

                'provider' =>
                    'meta',

                'provider_message_id' =>
                    'existing-id',

                'sent_at' =>
                    now(),
            ]);

        app(
            TenantContext::class
        )->clear();

        $job = new SendWhatsAppMessageJob(
            $tenant->id,
            $message->id,
            'meta'
        );

        app()->call([
            $job,
            'handle',
        ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $fresh = $message->fresh();

        $this->assertSame(
            WhatsAppMessageStatus::SENT,
            $fresh->status
        );

        $this->assertSame(
            'existing-id',
            $fresh->provider_message_id
        );
    }

    public function test_job_cannot_access_message_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'job-tenant-a'
        );

        $message = $this->message();

        $tenantB = $this->tenant(
            'job-tenant-b'
        );

        app(
            TenantContext::class
        )->clear();

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $job = new SendWhatsAppMessageJob(
            $tenantB->id,
            $message->id,
            'meta'
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

    public function test_job_has_three_attempts(): void
    {
        $job = new SendWhatsAppMessageJob(
            1,
            1,
            'meta'
        );

        $this->assertSame(
            3,
            $job->tries
        );
    }

    private function provider(
        string $name
    ): WhatsAppProvider {
        return new class(
            $name
        ) implements WhatsAppProvider {
            public function __construct(
                private readonly string $providerName
            ) {
            }

            public function name(): string
            {
                return $this->providerName;
            }

            public function send(
                WhatsAppMessage $message
            ): WhatsAppProviderResult {
                return new WhatsAppProviderResult(
                    strtolower(
                        trim(
                            $this->providerName
                        )
                    ),
                    'job-provider-message'
                );
            }
        };
    }

    private function config(
        string $provider
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    $provider,

                'sender_id' =>
                    'sender-id',

                'active' =>
                    true,

                'settings' =>
                    [
                        'token' =>
                            'secret',
                    ],
            ]);
    }

    private function message(): WhatsAppMessage
    {
        return WhatsAppMessage::query()
            ->create([
                'phone' =>
                    '5585999999999',

                'body' =>
                    'Mensagem WhatsApp',
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