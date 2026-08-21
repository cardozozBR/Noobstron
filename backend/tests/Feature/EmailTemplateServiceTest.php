<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Services\EmailTemplateService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_template_can_be_created(): void
    {
        $this->tenant(
            'email-template-service-create'
        );

        $template = app(
            EmailTemplateService::class
        )->create([
            'name' =>
                '  Proposta pronta  ',

            'subject_template' =>
                '  Sua proposta {{proposal_number}} está pronta  ',

            'body_template' =>
                '  Olá {{customer_name}}, veja a proposta {{proposal_number}}.  ',
        ]);

        $this->assertSame(
            'Proposta pronta',
            $template->name
        );

        $this->assertSame(
            'Sua proposta {{proposal_number}} está pronta',
            $template->subject_template
        );
    }

    public function test_email_template_can_be_partially_updated(): void
    {
        $this->tenant(
            'email-template-service-update'
        );

        $service = app(
            EmailTemplateService::class
        );

        $template = $this->template();

        $updated = $service->update(
            $template,
            [
                'name' =>
                    'Novo nome',
            ]
        );

        $this->assertSame(
            'Novo nome',
            $updated->name
        );

        $this->assertSame(
            'Olá {{customer_name}}',
            $updated->subject_template
        );

        $this->assertSame(
            'Mensagem para {{customer_name}}.',
            $updated->body_template
        );
    }

    public function test_template_can_render_subject_and_body(): void
    {
        $this->tenant(
            'email-template-render'
        );

        $template =
            EmailTemplate::query()->create([
                'name' =>
                    'Proposta',

                'subject_template' =>
                    'Proposta {{proposal_number}} para {{customer_name}}',

                'body_template' =>
                    'Olá {{customer_name}}, sua proposta {{proposal_number}} está disponível.',
            ]);

        $result = app(
            EmailTemplateService::class
        )->render(
            $template,
            [
                'proposal_number' =>
                    'PROP-100',

                'customer_name' =>
                    'Maria',
            ]
        );

        $this->assertSame(
            'Proposta PROP-100 para Maria',
            $result['subject']
        );

        $this->assertSame(
            'Olá Maria, sua proposta PROP-100 está disponível.',
            $result['body']
        );
    }

    public function test_placeholders_can_be_discovered(): void
    {
        $this->tenant(
            'email-template-placeholders'
        );

        $result = app(
            EmailTemplateService::class
        )->placeholders(
            'Olá {{customer_name}}, proposta {{ proposal_number }} de {{company_name}}.'
        );

        sort(
            $result
        );

        $this->assertSame(
            [
                'company_name',
                'customer_name',
                'proposal_number',
            ],
            $result
        );
    }

    public function test_duplicate_placeholder_is_returned_once(): void
    {
        $this->tenant(
            'email-template-duplicate-placeholder'
        );

        $result = app(
            EmailTemplateService::class
        )->placeholders(
            '{{customer_name}} - {{customer_name}}'
        );

        $this->assertSame(
            [
                'customer_name',
            ],
            $result
        );
    }

    public function test_missing_template_variable_is_rejected(): void
    {
        $this->tenant(
            'email-template-missing'
        );

        $template =
            EmailTemplate::query()->create([
                'name' =>
                    'Missing',

                'subject_template' =>
                    'Olá {{customer_name}}',

                'body_template' =>
                    'Proposta {{proposal_number}}',
            ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailTemplateService::class
        )->render(
            $template,
            [
                'customer_name' =>
                    'Maria',
            ]
        );
    }

    public function test_unknown_template_variable_is_rejected(): void
    {
        $this->tenant(
            'email-template-unknown'
        );

        $template = $this->template();

        $this->expectException(
            RuntimeException::class
        );

        app(
            EmailTemplateService::class
        )->render(
            $template,
            [
                'customer_name' =>
                    'Maria',

                'unexpected' =>
                    'value',
            ]
        );
    }

    public function test_template_without_placeholders_can_be_rendered(): void
    {
        $this->tenant(
            'email-template-static'
        );

        $template =
            EmailTemplate::query()->create([
                'name' =>
                    'Static',

                'subject_template' =>
                    'Bem-vindo',

                'body_template' =>
                    'Obrigado por escolher nossa empresa.',
            ]);

        $result = app(
            EmailTemplateService::class
        )->render(
            $template,
            []
        );

        $this->assertSame(
            'Bem-vindo',
            $result['subject']
        );

        $this->assertSame(
            'Obrigado por escolher nossa empresa.',
            $result['body']
        );
    }

    public function test_other_tenant_template_cannot_be_updated(): void
    {
        $this->tenant(
            'email-template-update-a'
        );

        $template = $this->template();

        $this->tenant(
            'email-template-update-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailTemplateService::class
        )->update(
            $template,
            [
                'name' =>
                    'Forbidden',
            ]
        );
    }

    public function test_other_tenant_template_cannot_be_rendered(): void
    {
        $this->tenant(
            'email-template-render-a'
        );

        $template = $this->template();

        $this->tenant(
            'email-template-render-b'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            EmailTemplateService::class
        )->render(
            $template,
            [
                'customer_name' =>
                    'Maria',
            ]
        );
    }

    public function test_common_customer_template_can_be_rendered(): void
    {
        $this->tenant(
            'email-template-customer'
        );

        $template =
            EmailTemplate::query()->create([
                'name' =>
                    'Relacionamento',

                'subject_template' =>
                    'Olá {{customer_name}}',

                'body_template' =>
                    '{{company_name}} agradece seu contato, {{customer_name}}.',
            ]);

        $result = app(
            EmailTemplateService::class
        )->render(
            $template,
            [
                'customer_name' =>
                    'Carlos',

                'company_name' =>
                    'Nossa Empresa',
            ]
        );

        $this->assertSame(
            'Olá Carlos',
            $result['subject']
        );

        $this->assertSame(
            'Nossa Empresa agradece seu contato, Carlos.',
            $result['body']
        );
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::query()->create([
            'name' =>
                'Boas-vindas',

            'subject_template' =>
                'Olá {{customer_name}}',

            'body_template' =>
                'Mensagem para {{customer_name}}.',
        ]);
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
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

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}