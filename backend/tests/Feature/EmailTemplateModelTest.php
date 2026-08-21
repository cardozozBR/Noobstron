<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmailTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_template_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'email-template-create'
        );

        $template = $this->template();

        $this->assertSame(
            $tenant->id,
            $template->tenant_id
        );
    }

    public function test_email_template_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'email-template-a'
        );

        $templateA = $this->template(
            'Template A'
        );

        $this->tenant(
            'email-template-b'
        );

        $this->template(
            'Template B'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $templates =
            EmailTemplate::query()
                ->get();

        $this->assertCount(
            1,
            $templates
        );

        $this->assertSame(
            $templateA->id,
            $templates->first()->id
        );
    }

    public function test_email_template_from_other_tenant_cannot_be_found(): void
    {
        $this->tenant(
            'email-template-find-a'
        );

        $template = $this->template();

        $this->tenant(
            'email-template-find-b'
        );

        $this->assertNull(
            EmailTemplate::query()->find(
                $template->id
            )
        );
    }

    public function test_email_template_normalizes_text_fields(): void
    {
        $this->tenant(
            'email-template-normalize'
        );

        $template =
            EmailTemplate::query()->create([
                'name' =>
                    '  Boas-vindas  ',

                'subject_template' =>
                    '  Olá {{customer_name}}  ',

                'body_template' =>
                    '  Seja bem-vindo, {{customer_name}}!  ',
            ]);

        $this->assertSame(
            'Boas-vindas',
            $template->name
        );

        $this->assertSame(
            'Olá {{customer_name}}',
            $template->subject_template
        );

        $this->assertSame(
            'Seja bem-vindo, {{customer_name}}!',
            $template->body_template
        );
    }

    public function test_blank_template_name_is_rejected(): void
    {
        $this->tenant(
            'email-template-empty-name'
        );

        $this->expectException(
            RuntimeException::class
        );

        EmailTemplate::query()->create([
            'name' =>
                '   ',

            'subject_template' =>
                'Assunto',

            'body_template' =>
                'Corpo',
        ]);
    }

    public function test_blank_subject_template_is_rejected(): void
    {
        $this->tenant(
            'email-template-empty-subject'
        );

        $this->expectException(
            RuntimeException::class
        );

        EmailTemplate::query()->create([
            'name' =>
                'Template',

            'subject_template' =>
                '   ',

            'body_template' =>
                'Corpo',
        ]);
    }

    public function test_blank_body_template_is_rejected(): void
    {
        $this->tenant(
            'email-template-empty-body'
        );

        $this->expectException(
            RuntimeException::class
        );

        EmailTemplate::query()->create([
            'name' =>
                'Template',

            'subject_template' =>
                'Assunto',

            'body_template' =>
                '   ',
        ]);
    }

    public function test_template_name_can_repeat_between_tenants(): void
    {
        $this->tenant(
            'email-template-repeat-a'
        );

        $first = $this->template(
            'Follow-up'
        );

        $this->tenant(
            'email-template-repeat-b'
        );

        $second = $this->template(
            'Follow-up'
        );

        $this->assertNotSame(
            $first->tenant_id,
            $second->tenant_id
        );

        $this->assertSame(
            'Follow-up',
            $second->name
        );
    }

    public function test_tenant_has_email_templates_relation(): void
    {
        $tenant = $this->tenant(
            'email-template-relation'
        );

        $template = $this->template();

        $this->assertTrue(
            $tenant
                ->emailTemplates()
                ->whereKey(
                    $template->id
                )
                ->exists()
        );
    }

    private function template(
        string $name = 'Boas-vindas'
    ): EmailTemplate {
        return EmailTemplate::query()->create([
            'name' =>
                $name,

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