<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppProviderConfig;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppProviderConfigModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_config_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'provider-config-create'
        );

        $config = $this->config();

        $this->assertSame(
            $tenant->id,
            $config->tenant_id
        );
    }

    public function test_provider_config_defaults_to_active(): void
    {
        $this->tenant(
            'provider-config-active'
        );

        $this->assertTrue(
            $this->config()->active
        );
    }

    public function test_provider_and_sender_are_normalized(): void
    {
        $this->tenant(
            'provider-config-normalize'
        );

        $config = WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    '  META  ',

                'sender_id' =>
                    '  phone-number-id  ',

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

        $this->assertSame(
            'phone-number-id',
            $config->sender_id
        );

        $this->assertSame(
            'secret',
            $config->settings['token']
        );
    }

    public function test_blank_provider_is_rejected(): void
    {
        $this->tenant(
            'provider-config-blank-provider'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    '   ',

                'sender_id' =>
                    'sender',
            ]);
    }

    public function test_blank_sender_is_rejected(): void
    {
        $this->tenant(
            'provider-config-blank-sender'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    'meta',

                'sender_id' =>
                    '   ',
            ]);
    }

    public function test_provider_configs_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'provider-config-a'
        );

        $configA = $this->config(
            'meta'
        );

        $this->tenant(
            'provider-config-b'
        );

        $configB = $this->config(
            'twilio'
        );

        $this->assertSame(
            1,
            WhatsAppProviderConfig::query()
                ->count()
        );

        $this->assertSame(
            $configB->id,
            WhatsAppProviderConfig::query()
                ->firstOrFail()
                ->id
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->assertSame(
            $configA->id,
            WhatsAppProviderConfig::query()
                ->firstOrFail()
                ->id
        );
    }

    public function test_provider_config_from_other_tenant_cannot_be_found(): void
    {
        $this->tenant(
            'provider-config-other-a'
        );

        $config = $this->config();

        $this->tenant(
            'provider-config-other-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        WhatsAppProviderConfig::query()
            ->findOrFail(
                $config->id
            );
    }

    public function test_tenant_has_provider_configs_relation(): void
    {
        $tenant = $this->tenant(
            'provider-config-relation'
        );

        $this->config(
            'meta'
        );

        $this->config(
            'twilio'
        );

        $this->assertCount(
            2,
            $tenant
                ->whatsAppProviderConfigs()
                ->get()
        );
    }

    private function config(
        string $provider = 'meta'
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create([
                'provider' =>
                    $provider,

                'sender_id' =>
                    $provider . '-sender',

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