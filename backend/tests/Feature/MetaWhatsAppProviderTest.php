<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProviderConfig;
use App\Models\WhatsAppTemplate;
use App\Services\MetaWhatsAppProvider;
use App\Services\TenantContext;
use App\Services\WhatsAppProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MetaWhatsAppProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_provider_is_registered(): void
    {
        $registry = app(
            WhatsAppProviderRegistry::class
        );

        $this->assertTrue(
            $registry->has('meta')
        );

        $this->assertInstanceOf(
            MetaWhatsAppProvider::class,
            $registry->get('meta')
        );
    }

    public function test_meta_provider_sends_text_message(): void
    {
        $this->tenant();

        WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => [
                    'token' => 'meta-test-token',
                    'graph_version' => 'v-test',
                ],
                'active' => true,
            ]);

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '55 (85) 99999-9999',
                'recipient_name' => 'Cliente',
                'body' => 'Mensagem de teste.',
            ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(
                [
                    'messaging_product' => 'whatsapp',

                    'messages' => [
                        [
                            'id' => 'wamid.test-message-id',
                        ],
                    ],
                ],
                200
            ),
        ]);

        $result = app(
            MetaWhatsAppProvider::class
        )->send(
            $message
        );

        $this->assertSame(
            'meta',
            $result->provider
        );

        $this->assertSame(
            'wamid.test-message-id',
            $result->messageId
        );

        Http::assertSent(
            function ($request): bool {
                return
                    $request->url() ===
                    'https://graph.facebook.com/v-test/123456789/messages'
                    && $request->hasHeader(
                        'Authorization',
                        'Bearer meta-test-token'
                    )
                    && $request[
                        'messaging_product'
                    ] === 'whatsapp'
                    && $request[
                        'recipient_type'
                    ] === 'individual'
                    && $request['to'] ===
                        '5585999999999'
                    && $request['type'] ===
                        'text'
                    && $request[
                        'text'
                    ]['body'] ===
                        'Mensagem de teste.'
                    && $request[
                        'text'
                    ]['preview_url'] ===
                        false;
            }
        );
    }

    public function test_meta_provider_sends_template_message(): void
    {
        $this->tenant();

        WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => [
                    'token' => 'meta-test-token',
                    'graph_version' => 'v-test',
                ],
                'active' => true,
            ]);

        $template = WhatsAppTemplate::query()
            ->create([
                'name' => 'Confirmação de atendimento',
                'body_template' => 'Olá {{customer_name}}, seu atendimento foi confirmado.',
                'provider' => 'meta',
                'provider_template_name' => 'confirmacao_atendimento',
                'language' => 'pt_BR',
                'active' => true,
            ]);

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '55 (85) 99999-9999',
                'recipient_name' => 'Cliente',
                'body' => 'Olá Cliente, seu atendimento foi confirmado.',
                'whatsapp_template_id' => $template->id,
                'template_variables' => [
                    'customer_name' => 'Cliente',
                ],
            ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(
                [
                    'messaging_product' => 'whatsapp',
                    'messages' => [
                        [
                            'id' => 'wamid.template-test-id',
                        ],
                    ],
                ],
                200
            ),
        ]);

        $result = app(
            MetaWhatsAppProvider::class
        )->send(
            $message
        );

        $this->assertSame(
            'meta',
            $result->provider
        );

        $this->assertSame(
            'wamid.template-test-id',
            $result->messageId
        );

        Http::assertSent(
            function ($request): bool {
                return
                    $request['messaging_product'] ===
                        'whatsapp'
                    && $request['recipient_type'] ===
                        'individual'
                    && $request['to'] ===
                        '5585999999999'
                    && $request['type'] ===
                        'template'
                    && $request['template']['name'] ===
                        'confirmacao_atendimento'
                    && $request['template']['language']['code'] ===
                        'pt_BR'
                    && $request['template']['components'][0]['type'] ===
                        'body'
                    && $request['template']['components'][0]['parameters'][0]['type'] ===
                        'text'
                    && $request['template']['components'][0]['parameters'][0]['text'] ===
                        'Cliente';
            }
        );
    }
    public function test_meta_provider_requires_token(): void
    {
        $this->tenant();

        WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => [
                    'graph_version' => 'v-test',
                ],
                'active' => true,
            ]);

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',
                'body' => 'Teste.',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Meta WhatsApp access token is not configured.'
        );

        app(
            MetaWhatsAppProvider::class
        )->send(
            $message
        );
    }

    public function test_meta_provider_requires_graph_version(): void
    {
        $this->tenant();

        WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => [
                    'token' => 'meta-test-token',
                ],
                'active' => true,
            ]);

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',
                'body' => 'Teste.',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Meta WhatsApp Graph API version is not configured.'
        );

        app(
            MetaWhatsAppProvider::class
        )->send(
            $message
        );
    }

    public function test_meta_provider_rejects_response_without_message_id(): void
    {
        $this->tenant();

        WhatsAppProviderConfig::query()
            ->create([
                'provider' => 'meta',
                'sender_id' => '123456789',
                'settings' => [
                    'token' => 'meta-test-token',
                    'graph_version' => 'v-test',
                ],
                'active' => true,
            ]);

        $message = WhatsAppMessage::query()
            ->create([
                'phone' => '5585999999999',
                'body' => 'Teste.',
            ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(
                [
                    'messaging_product' => 'whatsapp',
                ],
                200
            ),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Meta WhatsApp response does not contain a message id.'
        );

        app(
            MetaWhatsAppProvider::class
        )->send(
            $message
        );
    }

    private function tenant(): Tenant
    {
        $tenant = Tenant::query()
            ->create([
                'name' => 'Meta WhatsApp Tenant',
                'slug' => 'meta-whatsapp-tenant',
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
