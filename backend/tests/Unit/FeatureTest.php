<?php

namespace Tests\Unit;

use App\Enums\Feature;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    public function test_feature_catalog_contains_expected_values(): void
    {
        $this->assertSame(
            [
                'users',
                'audit',
                'branding',
            'leads',
            'customers',
                        'imports',
            'pipelines',
            'opportunities',
            'activities',
            'catalog',
            'proposals',
'sales',
            'receivables',
            'charges',
            'financial_indicators',
            'email',
            'whatsapp',
            'inbox',
            'ai',
        ],
            array_map(
                fn (Feature $feature) => $feature->value,
                Feature::cases()
            )
        );
    }

    public function test_feature_labels_are_defined(): void
    {
        $this->assertSame(
            'Usuários',
            Feature::USERS->label()
        );

        $this->assertSame(
            'Auditoria',
            Feature::AUDIT->label()
        );

        $this->assertSame(
            'Branding',
            Feature::BRANDING->label()
        );
    }

    public function test_feature_can_be_restored_from_value(): void
    {
        $this->assertSame(
            Feature::AUDIT,
            Feature::from('audit')
        );
    }
}
