<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppWebhookNormalizer;
use App\Contracts\WhatsAppWebhookVerifier;
use App\Enums\WhatsAppWebhookEventType;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Services\TenantContext;
use App\Support\WhatsAppWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WhatsAppWebhookHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_processes_inbound_event(): void
    {
        $tenant = $this->tenant(
            'webhook-http-valid'
        );

        $this->bindProvider(
            true,
            [
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    'fake',
                    'http-inbound-1',
                    '5585999999999',
                    'Mensagem recebida'
                ),
            ]
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenant->slug}/fake",
            [
                'event' =>
                    'ignored-by-fake-normalizer',
            ]
        )
            ->assertOk()
            ->assertJson([
                'ok' =>
                    true,

                'processed' =>
                    1,
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $this->assertDatabaseHas(
            'whatsapp_messages',
            [
                'tenant_id' =>
                    $tenant->id,

                'provider' =>
                    'fake',

                'provider_message_id' =>
                    'http-inbound-1',

                'direction' =>
                    'inbound',
            ]
        );
    }

    public function test_invalid_signature_returns_401(): void
    {
        $tenant = $this->tenant(
            'webhook-http-invalid'
        );

        $this->bindProvider(
            false,
            []
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenant->slug}/fake",
            []
        )
            ->assertUnauthorized()
            ->assertJson([
                'ok' =>
                    false,
            ]);
    }

    public function test_unknown_provider_returns_404(): void
    {
        $tenant = $this->tenant(
            'webhook-http-provider'
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenant->slug}/unknown",
            []
        )
            ->assertNotFound();
    }

    public function test_unknown_tenant_returns_404(): void
    {
        $this->bindProvider(
            true,
            []
        );

        $this->postJson(
            '/webhooks/whatsapp/not-found/fake',
            []
        )
            ->assertNotFound();
    }

    public function test_inactive_tenant_returns_404(): void
    {
        $tenant = Tenant::query()
            ->create([
                'name' =>
                    'Inactive tenant',

                'slug' =>
                    'webhook-http-inactive',

                'status' =>
                    'inactive',

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
        )->clear();

        $this->bindProvider(
            true,
            []
        );

        $this->postJson(
            "/webhooks/whatsapp/{$tenant->slug}/fake",
            []
        )
            ->assertNotFound();
    }

    public function test_multiple_events_are_processed(): void
    {
        $tenant = $this->tenant(
            'webhook-http-multiple'
        );

        $this->bindProvider(
            true,
            [
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    'fake',
                    'multi-1',
                    '5585999999991',
                    'Mensagem 1'
                ),

                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    'fake',
                    'multi-2',
                    '5585999999992',
                    'Mensagem 2'
                ),
            ]
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenant->slug}/fake",
            []
        )
            ->assertOk()
            ->assertJson([
                'ok' =>
                    true,

                'processed' =>
                    2,
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $this->assertSame(
            2,
            WhatsAppMessage::query()
                ->count()
        );
    }

    public function test_webhook_data_is_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'webhook-http-tenant-a'
        );

        $this->bindProvider(
            true,
            [
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    'fake',
                    'same-provider-id',
                    '5585999999991',
                    'Tenant A'
                ),
            ]
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenantA->slug}/fake",
            []
        )->assertOk();

        $tenantB = $this->tenant(
            'webhook-http-tenant-b'
        );

        $this->bindProvider(
            true,
            [
                new WhatsAppWebhookEvent(
                    WhatsAppWebhookEventType::RECEIVED,
                    'fake',
                    'same-provider-id',
                    '5585999999992',
                    'Tenant B'
                ),
            ]
        );

        app(
            TenantContext::class
        )->clear();

        $this->postJson(
            "/webhooks/whatsapp/{$tenantB->slug}/fake",
            []
        )->assertOk();

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->assertSame(
            'Tenant A',
            WhatsAppMessage::query()
                ->firstOrFail()
                ->body
        );

        app(
            TenantContext::class
        )->set(
            $tenantB
        );

        $this->assertSame(
            'Tenant B',
            WhatsAppMessage::query()
                ->firstOrFail()
                ->body
        );
    }

    private function bindProvider(
        bool $valid,
        array $events
    ): void {
        app()->instance(
            'whatsapp.webhook.verifier.fake',
            new class(
                $valid
            ) implements WhatsAppWebhookVerifier {
                public function __construct(
                    private readonly bool $valid
                ) {
                }

                public function verify(
                    Request $request
                ): bool {
                    return $this->valid;
                }
            }
        );

        app()->instance(
            'whatsapp.webhook.normalizer.fake',
            new class(
                $events
            ) implements WhatsAppWebhookNormalizer {
                public function __construct(
                    private readonly array $events
                ) {
                }

                public function normalize(
                    Request $request
                ): array {
                    return $this->events;
                }
            }
        );
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