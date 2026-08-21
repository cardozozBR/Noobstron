<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppAccessFoundationTest extends TestCase
{
    #[Test]
    public function whatsapp_feature_exists(): void
    {
        $this->assertSame(
            'whatsapp',
            Feature::WHATSAPP->value
        );

        $this->assertSame(
            'WhatsApp',
            Feature::WHATSAPP->label()
        );
    }

    #[Test]
    public function whatsapp_permissions_match_email_convention(): void
    {
        $this->assertSame(
            'whatsapp.view',
            Permission::WHATSAPP_VIEW->value
        );

        $this->assertSame(
            'whatsapp.create',
            Permission::WHATSAPP_CREATE->value
        );

        $this->assertSame(
            'whatsapp.send',
            Permission::WHATSAPP_SEND->value
        );

        $this->assertSame(
            'whatsapp.templates',
            Permission::WHATSAPP_TEMPLATES->value
        );
    }

    #[Test]
    public function whatsapp_permission_values_are_unique(): void
    {
        $values = array_map(
            static fn (Permission $permission): string =>
                $permission->value,
            Permission::cases()
        );

        $this->assertCount(
            count($values),
            array_unique($values)
        );
    }

    #[Test]
    public function whatsapp_feature_values_are_unique(): void
    {
        $values = array_map(
            static fn (Feature $feature): string =>
                $feature->value,
            Feature::cases()
        );

        $this->assertCount(
            count($values),
            array_unique($values)
        );
    }
}