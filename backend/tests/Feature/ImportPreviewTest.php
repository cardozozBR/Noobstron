<?php

namespace Tests\Feature;

use App\Enums\ImportTarget;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use App\Services\ImportPreviewService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPreviewTest extends TestCase
{
    use RefreshDatabase;
    private function tenant(
        string $slug = 'import-preview'
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function file(
        string $content
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            'import-preview-'
        );

        file_put_contents(
            $path,
            $content
        );

        return $path;
    }

    public function test_lead_preview_separates_valid_and_invalid_rows(): void
    {
        $path = $this->file(
            "Nome,E-mail,Status,Origem,Tags\n"
            . "Maria,MARIA@EXAMPLE.COM,qualified,website,\"vip,site\"\n"
            . ",invalid,invalid,manual,teste\n"
            . "Joao,joao@example.com,new,referral,novo\n"
        );

        try {
            $preview = app(
                ImportPreviewService::class
            )->preview(
                $path,
                ImportTarget::LEADS
            );

            $this->assertSame(
                3,
                $preview['row_count']
            );

            $this->assertSame(
                2,
                $preview['valid_count']
            );

            $this->assertSame(
                1,
                $preview['invalid_count']
            );

            $this->assertSame(
                2,
                $preview['valid_rows'][0]['line']
            );

            $this->assertSame(
                'maria@example.com',
                $preview['valid_rows'][0]['data']['email']
            );

            $this->assertSame(
                [
                    'vip',
                    'site',
                ],
                $preview['valid_rows'][0]['data']['tags']
            );

            $this->assertArrayHasKey(
                'name',
                $preview['invalid_rows'][0]['errors']
            );

            $this->assertArrayHasKey(
                'email',
                $preview['invalid_rows'][0]['errors']
            );

            $this->assertArrayHasKey(
                'status',
                $preview['invalid_rows'][0]['errors']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_customer_preview_normalizes_type_and_document(): void
    {
        $path = $this->file(
            "Tipo,Nome,Documento,Tipo Documento,País Documento,E-mail\n"
            . "PF,Maria,529.982.247-25,cpf,br,MARIA@EXAMPLE.COM\n"
        );

        try {
            $preview = app(
                ImportPreviewService::class
            )->preview(
                $path,
                ImportTarget::CUSTOMERS
            );

            $row =
                $preview['valid_rows'][0]['data'];

            $this->assertSame(
                'individual',
                $row['type']
            );

            $this->assertSame(
                '52998224725',
                $row['tax_identifier']
            );

            $this->assertSame(
                'CPF',
                $row['tax_identifier_type']
            );

            $this->assertSame(
                'BR',
                $row['tax_country_code']
            );

            $this->assertSame(
                'maria@example.com',
                $row['email']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_preview_does_not_persist_leads_or_customers(): void
    {
        $this->tenant(
            'import-preview-no-persist'
        );

        $leadPath = $this->file(
            "name,email\n"
            . "Maria,maria@example.com\n"
        );

        $customerPath = $this->file(
            "type,name\n"
            . "individual,Cliente\n"
        );

        try {
            app(
                ImportPreviewService::class
            )->preview(
                $leadPath,
                ImportTarget::LEADS
            );

            app(
                ImportPreviewService::class
            )->preview(
                $customerPath,
                ImportTarget::CUSTOMERS
            );

            $this->assertSame(
                0,
                Lead::query()->count()
            );

            $this->assertSame(
                0,
                Customer::query()->count()
            );
        } finally {
            @unlink($leadPath);
            @unlink($customerPath);
        }
    }

    public function test_preview_limit_only_limits_returned_rows(): void
    {
        $path = $this->file(
            "name,email\n"
            . "A,a@example.com\n"
            . "B,b@example.com\n"
            . "C,c@example.com\n"
        );

        try {
            $preview = app(
                ImportPreviewService::class
            )->preview(
                $path,
                ImportTarget::LEADS,
                ',',
                2
            );

            $this->assertSame(
                3,
                $preview['valid_count']
            );

            $this->assertCount(
                2,
                $preview['valid_rows']
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_invalid_preview_limit_is_rejected(): void
    {
        $path = $this->file(
            "name\nMaria\n"
        );

        try {
            $this->expectException(
                \RuntimeException::class
            );

            app(
                ImportPreviewService::class
            )->preview(
                $path,
                ImportTarget::LEADS,
                ',',
                0
            );
        } finally {
            @unlink($path);
        }
    }
}
