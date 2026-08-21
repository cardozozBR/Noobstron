<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\WhatsAppTemplate;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WhatsAppTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_template_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'whatsapp-template-create'
        );

        $template = $this->template();

        $this->assertSame(
            $tenant->id,
            $template->tenant_id
        );
    }

    public function test_whatsapp_template_defaults_to_active(): void
    {
        $this->tenant(
            'whatsapp-template-active'
        );

        $template = $this->template();

        $this->assertTrue(
            $template->active
        );
    }

    public function test_whatsapp_template_normalizes_fields(): void
    {
        $this->tenant(
            'whatsapp-template-normalize'
        );

        $template = WhatsAppTemplate::query()
            ->create([
                'name' =>
                    '  Boas-vindas  ',

                'body_template' =>
                    '  Olá {{customer_name}}  ',

                'provider' =>
                    '  META  ',

                'provider_template_name' =>
                    '  welcome_customer  ',

                'language' =>
                    '  PT_BR  ',
            ]);

        $this->assertSame(
            'Boas-vindas',
            $template->name
        );

        $this->assertSame(
            'Olá {{customer_name}}',
            $template->body_template
        );

        $this->assertSame(
            'meta',
            $template->provider
        );

        $this->assertSame(
            'welcome_customer',
            $template->provider_template_name
        );

        $this->assertSame(
            'pt_br',
            $template->language
        );
    }

    public function test_blank_name_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-template-name'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppTemplate::query()
            ->create([
                'name' =>
                    '   ',

                'body_template' =>
                    'Mensagem',
            ]);
    }

    public function test_blank_body_is_rejected(): void
    {
        $this->tenant(
            'whatsapp-template-body'
        );

        $this->expectException(
            RuntimeException::class
        );

        WhatsAppTemplate::query()
            ->create([
                'name' =>
                    'Template',

                'body_template' =>
                    '   ',
            ]);
    }

    public function test_template_queries_are_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'whatsapp-template-a'
        );

        $templateA = $this->template(
            'Template A'
        );

        $this->tenant(
            'whatsapp-template-b'
        );

        $templateB = $this->template(
            'Template B'
        );

        $this->assertSame(
            1,
            WhatsAppTemplate::query()
                ->count()
        );

        $this->assertSame(
            $templateB->id,
            WhatsAppTemplate::query()
                ->firstOrFail()
                ->id
        );

        app(
            TenantContext::class
        )->set(
            $tenantA
        );

        $this->assertSame(
            $templateA->id,
            WhatsAppTemplate::query()
                ->firstOrFail()
                ->id
        );
    }

    public function test_template_from_other_tenant_cannot_be_found(): void
    {
        $this->tenant(
            'whatsapp-template-other-a'
        );

        $template = $this->template();

        $this->tenant(
            'whatsapp-template-other-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        WhatsAppTemplate::query()
            ->findOrFail(
                $template->id
            );
    }

    public function test_tenant_has_whatsapp_templates_relation(): void
    {
        $tenant = $this->tenant(
            'whatsapp-template-relation'
        );

        $this->template(
            'Template 1'
        );

        $this->template(
            'Template 2'
        );

        $this->assertCount(
            2,
            $tenant
                ->whatsAppTemplates()
                ->get()
        );
    }

    private function template(
        string $name = 'Boas-vindas'
    ): WhatsAppTemplate {
        return WhatsAppTemplate::query()
            ->create([
                'name' =>
                    $name,

                'body_template' =>
                    'Olá {{customer_name}}',
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