<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppProviderConfig;
use App\Services\TenantContext;
use App\Services\WhatsAppProviderConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppProviderConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_config_can_be_created(): void
    {
        $this->tenant(
            'provider-service-create'
        );

        $config = $this->service()
            ->create([
                'provider' =>
                    'meta',

                'sender_id' =>
                    'sender-1',

                'settings' =>
                    [
                        'token' =>
                            'secret',
                    ],
            ]);

        $this->assertSame(
            'meta',
            $config->provider
        );
    }

    public function test_provider_config_can_be_updated(): void
    {
        $this->tenant(
            'provider-service-update'
        );

        $config = $this->config();

        $updated = $this->service()
            ->update(
                $config,
                [
                    'sender_id' =>
                        'new-sender',
                ]
            );

        $this->assertSame(
            'new-sender',
            $updated->sender_id
        );
    }

    public function test_active_provider_config_can_be_resolved(): void
    {
        $this->tenant(
            'provider-service-active'
        );

        $this->config(
            'meta',
            true
        );

        $resolved = $this->service()
            ->active(
                ' META '
            );

        $this->assertSame(
            'meta',
            $resolved->provider
        );
    }

    public function test_inactive_provider_config_is_not_resolved(): void
    {
        $this->tenant(
            'provider-service-inactive'
        );

        $this->config(
            'meta',
            false
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->active(
                'meta'
            );
    }

    public function test_other_tenant_config_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'provider-service-a'
        );

        $config = $this->config();

        $this->tenant(
            'provider-service-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->update(
                $config,
                [
                    'sender_id' =>
                        'forbidden',
                ]
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    private function service(): WhatsAppProviderConfigService
    {
        return app(
            WhatsAppProviderConfigService::class
        );
    }

    private function config(
        string $provider = 'meta',
        bool $active = true
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    $provider,

                'sender_id' =>
                    'sender',

                'active' =>
                    $active,

                'settings' =>
                    [
                        'token' =>
                            'secret',
                    ],
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