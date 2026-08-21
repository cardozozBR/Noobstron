<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrePhase16HardeningTest extends TestCase
{
    public function test_external_webhooks_are_explicitly_excluded_from_request_forgery_protection(): void
    {
        $bootstrap = file_get_contents(
            base_path('bootstrap/app.php')
        );

        $this->assertIsString($bootstrap);

        $this->assertStringContainsString(
            '$middleware->preventRequestForgery(except: [',
            $bootstrap
        );

        $this->assertStringContainsString(
            "'webhooks/whatsapp/*'",
            $bootstrap
        );

        $this->assertStringContainsString(
            "'webhooks/payment/*'",
            $bootstrap
        );
    }

    public function test_roadmap_points_to_phase_16_global_dashboard(): void
    {
        $roadmap = file_get_contents(
            base_path('ROADMAP.md')
        );

        $this->assertIsString($roadmap);

        $this->assertStringContainsString(
            '**Fase:** Administração da nossa plataforma',
            $roadmap
        );

        $this->assertStringContainsString(
            '**Etapa atual:** 16.1 — Painel global',
            $roadmap
        );
    }
}