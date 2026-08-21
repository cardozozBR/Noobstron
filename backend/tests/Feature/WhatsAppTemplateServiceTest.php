<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppTemplate;
use App\Services\TenantContext;
use App\Services\WhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_template_can_be_created(): void
    {
        $this->tenant(
            'whatsapp-template-service-create'
        );

        $template = $this->service()
            ->create([
                'name' =>
                    'Cobrança',

                'body_template' =>
                    'Olá {{customer}}, sua cobrança vence em {{due_date}}.',
            ]);

        $this->assertSame(
            'Cobrança',
            $template->name
        );
    }

    public function test_whatsapp_template_can_be_partially_updated(): void
    {
        $this->tenant(
            'whatsapp-template-service-update'
        );

        $template = $this->template();

        $updated = $this->service()
            ->update(
                $template,
                [
                    'active' =>
                        false,
                ]
            );

        $this->assertFalse(
            $updated->active
        );

        $this->assertSame(
            'Template',
            $updated->name
        );
    }

    public function test_placeholders_can_be_discovered(): void
    {
        $this->tenant(
            'whatsapp-template-placeholders'
        );

        $template = $this->template(
            'Olá {{customer}}, pedido {{order_id}} de {{customer}}.'
        );

        $this->assertSame(
            [
                'customer',
                'order_id',
            ],
            $this->service()
                ->placeholders(
                    $template
                )
        );
    }

    public function test_template_can_be_rendered(): void
    {
        $this->tenant(
            'whatsapp-template-render'
        );

        $template = $this->template(
            'Olá {{customer}}, seu pedido {{order_id}} está pronto.'
        );

        $rendered = $this->service()
            ->render(
                $template,
                [
                    'customer' =>
                        'Maria',

                    'order_id' =>
                        '123',
                ]
            );

        $this->assertSame(
            'Olá Maria, seu pedido 123 está pronto.',
            $rendered
        );
    }

    public function test_missing_variable_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-template-missing'
        );

        $template = $this->template(
            'Olá {{customer}}, pedido {{order_id}}.'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->render(
                $template,
                [
                    'customer' =>
                        'Maria',
                ]
            );
    }

    public function test_unknown_variable_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-template-unknown'
        );

        $template = $this->template(
            'Olá {{customer}}.'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->render(
                $template,
                [
                    'customer' =>
                        'Maria',

                    'order_id' =>
                        '123',
                ]
            );
    }

    public function test_template_without_placeholders_can_be_rendered(): void
    {
        $this->tenant(
            'whatsapp-template-no-vars'
        );

        $template = $this->template(
            'Mensagem fixa.'
        );

        $this->assertSame(
            'Mensagem fixa.',
            $this->service()
                ->render(
                    $template
                )
        );
    }

    public function test_other_tenant_template_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-template-update-a'
        );

        $template = $this->template();

        $this->tenant(
            'whatsapp-template-update-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->update(
                $template,
                [
                    'active' =>
                        false,
                ]
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    public function test_other_tenant_template_cannot_be_rendered(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-template-render-a'
        );

        $template = $this->template(
            'Olá {{customer}}'
        );

        $this->tenant(
            'whatsapp-template-render-b'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->service()
            ->render(
                $template,
                [
                    'customer' =>
                        'Maria',
                ]
            );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );
    }

    private function service(): WhatsAppTemplateService
    {
        return app(
            WhatsAppTemplateService::class
        );
    }

    private function template(
        string $body = 'Olá {{customer}}'
    ): WhatsAppTemplate {
        return WhatsAppTemplate::query()
            ->create([
                'name' =>
                    'Template',

                'body_template' =>
                    $body,
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