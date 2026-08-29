<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppProviderConfig;
use App\Services\MetaWhatsAppWebhookNormalizer;
use App\Services\MetaWhatsAppWebhookVerifier;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MetaWhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_webhook_verification_returns_challenge(): void
    {
        $tenant = $this->tenant();

        $this->config([
            'verify_token' => 'verify-secret',
        ]);

        app(
            TenantContext::class
        )->clear();

        $response = $this->get(
            "/webhooks/whatsapp/{$tenant->slug}/meta?"
            .'hub.mode=subscribe'
            .'&hub.verify_token=verify-secret'
            .'&hub.challenge=123456'
        );

        $response
            ->assertOk()
            ->assertSeeText('123456');
    }

    public function test_meta_webhook_verification_rejects_invalid_token(): void
    {
        $tenant = $this->tenant();

        $this->config([
            'verify_token' => 'verify-secret',
        ]);

        app(
            TenantContext::class
        )->clear();

        $this->get(
            "/webhooks/whatsapp/{$tenant->slug}/meta?"
            .'hub.mode=subscribe'
            .'&hub.verify_token=wrong'
            .'&hub.challenge=123456'
        )->assertForbidden();
    }

    public function test_meta_signature_verifier_accepts_valid_signature(): void
    {
        $this->tenant();

        $this->config([
            'app_secret' => 'app-secret',
        ]);

        $body = json_encode([
            'object' => 'whatsapp_business_account',
        ]);

        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',

                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='
                    .hash_hmac(
                        'sha256',
                        $body,
                        'app-secret'
                    ),
            ],
            $body
        );

        $this->assertTrue(
            app(
                MetaWhatsAppWebhookVerifier::class
            )->verify(
                $request
            )
        );
    }

    public function test_meta_normalizer_converts_received_text_message(): void
    {
        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'object' => 'whatsapp_business_account',

                'entry' => [
                    [
                        'changes' => [
                            [
                                'field' => 'messages',

                                'value' => [
                                    'contacts' => [
                                        [
                                            'profile' => [
                                                'name' => 'Maria Cliente',
                                            ],
                                            'wa_id' => '5585999999999',
                                        ],
                                    ],

                                    'messages' => [
                                        [
                                            'from' => '5585999999999',

                                            'id' => 'wamid.inbound',

                                            'type' => 'text',

                                            'text' => [
                                                'body' => 'Olá Noobstron',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $events = app(
            MetaWhatsAppWebhookNormalizer::class
        )->normalize(
            $request
        );

        $this->assertCount(1, $events);

        $this->assertSame(
            'received',
            $events[0]->type->value
        );

        $this->assertSame(
            'meta',
            $events[0]->provider
        );

        $this->assertSame(
            'wamid.inbound',
            $events[0]->providerMessageId
        );

        $this->assertSame(
            '5585999999999',
            $events[0]->phone
        );

        $this->assertSame(
            'Olá Noobstron',
            $events[0]->body
        );

        $this->assertSame(
            'Maria Cliente',
            $events[0]->recipientName
        );
    }

    public function test_meta_normalizer_converts_statuses_and_ignores_sent(): void
    {
        $request = Request::create(
            '/webhook',
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'object' => 'whatsapp_business_account',

                'entry' => [
                    [
                        'changes' => [
                            [
                                'field' => 'messages',

                                'value' => [
                                    'statuses' => [
                                        [
                                            'id' => 'wamid.sent',
                                            'status' => 'sent',
                                        ],
                                        [
                                            'id' => 'wamid.delivered',
                                            'status' => 'delivered',
                                        ],
                                        [
                                            'id' => 'wamid.read',
                                            'status' => 'read',
                                        ],
                                        [
                                            'id' => 'wamid.failed',
                                            'status' => 'failed',
                                            'errors' => [
                                                [
                                                    'message' => 'Destination unavailable',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $events = app(
            MetaWhatsAppWebhookNormalizer::class
        )->normalize(
            $request
        );

        $this->assertCount(
            3,
            $events
        );

        $this->assertSame(
            'delivered',
            $events[0]->type->value
        );

        $this->assertSame(
            'read',
            $events[1]->type->value
        );

        $this->assertSame(
            'failed',
            $events[2]->type->value
        );

        $this->assertSame(
            'Destination unavailable',
            $events[2]->failureReason
        );
    }

    private function tenant(): Tenant
    {
        $tenant = Tenant::query()
            ->create([
                'name' => 'Meta Webhook Tenant',
                'slug' => 'meta-webhook-tenant',
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

    private function config(
        array $settings
    ): WhatsAppProviderConfig {
        return WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => $settings,
                'active' => true,
            ]);
    }
}
