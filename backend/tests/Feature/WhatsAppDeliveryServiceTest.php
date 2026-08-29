<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppProvider;
use App\Enums\WhatsAppMessageStatus;
use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProviderConfig;
use App\Services\TenantContext;
use App\Services\WhatsAppDeliveryService;
use App\Services\WhatsAppProviderRegistry;
use App\Support\WhatsAppProviderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_message_can_be_delivered_through_registered_provider(): void
    {
        $this->tenant(
            'delivery-success'
        );

        $this->config(
            'meta'
        );

        $this->registry()
            ->register(
                $this->successfulProvider(
                    'meta'
                )
            );

        $message = $this->message();

        $updated = app(
            WhatsAppDeliveryService::class
        )->send(
            $message,
            'meta'
        );

        $this->assertSame(
            WhatsAppMessageStatus::SENT,
            $updated->status
        );

        $this->assertSame(
            'meta',
            $updated->provider
        );

        $this->assertSame(
            'provider-message-123',
            $updated->provider_message_id
        );
    }

    public function test_provider_failure_marks_message_as_failed(): void
    {
        $this->tenant(
            'delivery-failure'
        );

        $this->config(
            'meta'
        );

        $this->registry()
            ->register(
                $this->failingProvider(
                    'meta'
                )
            );

        $message = $this->message();

        $updated = app(
            WhatsAppDeliveryService::class
        )->send(
            $message,
            'meta'
        );

        $this->assertSame(
            WhatsAppMessageStatus::FAILED,
            $updated->status
        );

        $this->assertSame(
            'Provider unavailable',
            $updated->failure_reason
        );
    }

    public function test_inactive_configuration_blocks_delivery(): void
    {
        $this->tenant(
            'delivery-inactive'
        );

        $this->config(
            'meta',
            false
        );

        $this->registry()
            ->register(
                $this->successfulProvider(
                    'meta'
                )
            );

        $message = $this->message();

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppDeliveryService::class
        )->send(
            $message,
            'meta'
        );
    }

    public function test_unregistered_provider_blocks_delivery(): void
    {
        $this->tenant(
            'delivery-unregistered'
        );

        $this->config(
            'unknown'
        );

        $message = $this->message();

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppDeliveryService::class
        )->send(
            $message,
            'unknown'
        );
    }

    public function test_non_pending_message_cannot_be_delivered(): void
    {
        $this->tenant(
            'delivery-non-pending'
        );

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',

                'body' => 'Mensagem',

                'status' => WhatsAppMessageStatus::RECEIVED,

                'direction' => 'inbound',

                'received_at' => now(),
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            WhatsAppDeliveryService::class
        )->send(
            $message,
            'meta'
        );
    }

    private function registry(): WhatsAppProviderRegistry
    {
        return app(
            WhatsAppProviderRegistry::class
        );
    }

    private function successfulProvider(
        string $name
    ): WhatsAppProvider {
        return new class($name) implements WhatsAppProvider
        {
            public function __construct(
                private readonly string $providerName
            ) {}

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
                    'provider-message-123'
                );
            }
        };
    }

    private function failingProvider(
        string $name
    ): WhatsAppProvider {
        return new class($name) implements WhatsAppProvider
        {
            public function __construct(
                private readonly string $providerName
            ) {}

            public function name(): string
            {
                return $this->providerName;
            }

            public function send(
                WhatsAppMessage $message
            ): WhatsAppProviderResult {
                throw new RuntimeException(
                    'Provider unavailable'
                );
            }
        };
    }

    private function config(
        string $provider,
        bool $active = true
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create([
                'provider' => $provider,

                'sender_id' => 'sender-id',

                'active' => $active,

                'settings' => [
                    'token' => 'secret',
                ],
            ]);
    }

    private function message(): WhatsAppMessage
    {
        return WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',

                'body' => 'Mensagem WhatsApp',
            ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' => 'Tenant '.$slug,

                'slug' => $slug,

                'status' => 'active',

                'country_code' => 'BR',

                'locale' => 'pt-BR',

                'timezone' => 'America/Fortaleza',

                'currency' => 'BRL',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }
}
