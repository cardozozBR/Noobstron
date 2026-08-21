<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppUiTest extends TestCase
{
    public function test_whatsapp_translation_files_exist(): void
    {
        foreach ([
            'pt-BR',
            'en',
            'es',
            'ja',
            'zh-CN',
        ] as $locale) {
            $this->assertFileExists(
                lang_path(
                    $locale . '/whatsapp.php'
                )
            );
        }
    }

    public function test_whatsapp_ai_rewrite_ui_contract(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/whatsapp/create.blade.php'
            )
        );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            "Feature::AI",
            $view
        );

        $this->assertStringContainsString(
            "@can('ai.use')",
            $view
        );

        $this->assertStringContainsString(
            "id=\"whatsapp_ai_rewrite\"",
            $view
        );

        $this->assertStringContainsString(
            "route('ai.rewrite')",
            $view
        );

        $this->assertStringContainsString(
            "fetch(button.dataset.url",
            $view
        );

        $this->assertStringContainsString(
            "payload?.data?.content",
            $view
        );

        $this->assertStringContainsString(
            "body.value = content",
            $view
        );
    }
    public function test_whatsapp_views_exist(): void
    {
        foreach ([
            resource_path(
                'views/whatsapp/index.blade.php'
            ),
            resource_path(
                'views/whatsapp/create.blade.php'
            ),
            resource_path(
                'views/whatsapp/templates.blade.php'
            ),
        ] as $view) {
            $this->assertFileExists(
                $view
            );
        }
    }
}