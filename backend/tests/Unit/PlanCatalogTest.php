<?php

namespace Tests\Unit;

use App\Support\PlanCatalog;
use PHPUnit\Framework\TestCase;

class PlanCatalogTest extends TestCase
{
    public function test_catalog_contains_expected_plans(): void
    {
        $this->assertSame(
            [
                'start',
                'pro',
                'business',
                'enterprise',
            ],
            PlanCatalog::codes()
        );
    }

    public function test_catalog_definitions_have_stable_codes_and_names(): void
    {
        $this->assertSame(
            [
                'start' => [
                    'code' => 'start',
                    'name' => 'Start',
                ],
                'pro' => [
                    'code' => 'pro',
                    'name' => 'Pro',
                ],
                'business' => [
                    'code' => 'business',
                    'name' => 'Business',
                ],
                'enterprise' => [
                    'code' => 'enterprise',
                    'name' => 'Enterprise',
                ],
            ],
            PlanCatalog::definitions()
        );
    }
}