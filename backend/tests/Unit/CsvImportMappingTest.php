<?php

namespace Tests\Unit;

use App\Enums\ImportTarget;
use App\Services\CsvImportMapping;
use RuntimeException;
use Tests\TestCase;

class CsvImportMappingTest extends TestCase
{
    public function test_lead_mapping_is_built_from_header(): void
    {
        $result = app(
            CsvImportMapping::class
        )->build(
            [
                'Nome',
                'E-mail',
                'Telefone',
            ],
            ImportTarget::LEADS
        );

        $this->assertSame(
            [
                0 => 'name',
                1 => 'email',
                2 => 'phone',
            ],
            $result['mapping']
        );
    }

    public function test_unknown_columns_are_ignored(): void
    {
        $result = app(
            CsvImportMapping::class
        )->build(
            [
                'name',
                'custom field',
            ],
            ImportTarget::LEADS
        );

        $this->assertSame(
            [
                'custom_field',
            ],
            $result['ignored']
        );
    }

    public function test_missing_required_lead_name_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            CsvImportMapping::class
        )->build(
            [
                'email',
            ],
            ImportTarget::LEADS
        );
    }

    public function test_customer_requires_type(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        app(
            CsvImportMapping::class
        )->build(
            [
                'name',
                'email',
            ],
            ImportTarget::CUSTOMERS
        );
    }

    public function test_customer_mapping_supports_document_fields(): void
    {
        $result = app(
            CsvImportMapping::class
        )->build(
            [
                'Tipo',
                'Nome',
                'Documento',
                'Tipo documento',
            ],
            ImportTarget::CUSTOMERS
        );

        $this->assertSame(
            [
                0 => 'type',
                1 => 'name',
                2 => 'tax_identifier',
                3 => 'tax_identifier_type',
            ],
            $result['mapping']
        );
    }
}
