<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLearningCenterTest extends TestCase
{
    public function test_learning_center_is_public(): void
    {
        $this->get('/aprender')
            ->assertOk()
            ->assertSee('Central de Aprendizado Noobstron')
            ->assertSee('Primeiros passos com o Noobstron');
    }

    public function test_getting_started_guide_is_public(): void
    {
        $this->get('/aprender/primeiros-passos')
            ->assertOk()
            ->assertSee('Do cadastro à primeira venda')
            ->assertSee('Configure sua empresa')
            ->assertSee('Registre a primeira venda');
    }

    public function test_learning_pages_are_in_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/aprender', false)
            ->assertSee('/aprender/primeiros-passos', false);
    }
}