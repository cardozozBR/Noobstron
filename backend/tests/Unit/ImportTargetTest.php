<?php

namespace Tests\Unit;

use App\Enums\ImportTarget;
use Tests\TestCase;

class ImportTargetTest extends TestCase
{
    public function test_leads_require_name(): void
    {
        $this->assertSame(
            ['name'],
            ImportTarget::LEADS
                ->requiredFields()
        );
    }

    public function test_customers_require_name_and_type(): void
    {
        $this->assertSame(
            [
                'name',
                'type',
            ],
            ImportTarget::CUSTOMERS
                ->requiredFields()
        );
    }

    public function test_targets_have_supported_fields(): void
    {
        $this->assertContains(
            'email',
            ImportTarget::LEADS
                ->supportedFields()
        );

        $this->assertContains(
            'tax_identifier',
            ImportTarget::CUSTOMERS
                ->supportedFields()
        );
    }
}
