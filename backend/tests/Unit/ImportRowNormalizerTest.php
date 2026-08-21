<?php

namespace Tests\Unit;

use App\Enums\ImportTarget;
use App\Services\ImportRowNormalizer;
use Tests\TestCase;

class ImportRowNormalizerTest extends TestCase
{
    public function test_lead_values_are_normalized(): void
    {
        $result = app(
            ImportRowNormalizer::class
        )->normalize(
            [
                'name' => ' Maria ',
                'email' => ' MARIA@EXAMPLE.COM ',
                'phone' => '(85) 99999-9999',
                'status' => 'QUALIFIED',
                'source' => 'WEBSITE',
                'tags' => 'vip, site | novo',
            ],
            ImportTarget::LEADS
        );

        $this->assertSame(
            'Maria',
            $result['name']
        );

        $this->assertSame(
            'maria@example.com',
            $result['email']
        );

        $this->assertSame(
            '85999999999',
            $result['phone']
        );

        $this->assertSame(
            'qualified',
            $result['status']
        );

        $this->assertSame(
            'website',
            $result['source']
        );

        $this->assertSame(
            [
                'vip',
                'site',
                'novo',
            ],
            $result['tags']
        );
    }

    public function test_customer_type_aliases_are_normalized(): void
    {
        $service = app(
            ImportRowNormalizer::class
        );

        $this->assertSame(
            'individual',
            $service->normalize(
                [
                    'type' =>
                        'Pessoa Física',
                ],
                ImportTarget::CUSTOMERS
            )['type']
        );

        $this->assertSame(
            'company',
            $service->normalize(
                [
                    'type' => 'PJ',
                ],
                ImportTarget::CUSTOMERS
            )['type']
        );
    }

    public function test_document_fields_are_normalized(): void
    {
        $result = app(
            ImportRowNormalizer::class
        )->normalize(
            [
                'tax_country_code' => ' br ',
                'tax_identifier_type' => ' cpf ',
                'tax_identifier' =>
                    '529.982.247-25',
            ],
            ImportTarget::CUSTOMERS
        );

        $this->assertSame(
            'BR',
            $result['tax_country_code']
        );

        $this->assertSame(
            'CPF',
            $result['tax_identifier_type']
        );

        $this->assertSame(
            '52998224725',
            $result['tax_identifier']
        );
    }
}
