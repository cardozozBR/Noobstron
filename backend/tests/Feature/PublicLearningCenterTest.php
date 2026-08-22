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
            ->assertSee('Primeiros passos com o Noobstron')
            ->assertSee('Organize seus clientes')
            ->assertSee('Estruture seu processo de vendas')
            ->assertSee('Melhore seu follow-up')
            ->assertSee('Centralize a comunicação');
    }

    public function test_getting_started_guide_is_public(): void
    {
        $this->get('/aprender/primeiros-passos')
            ->assertOk()
            ->assertSee('Do cadastro à primeira venda')
            ->assertSee('Configure sua empresa')
            ->assertSee('Registre a primeira venda');
    }

    public function test_customers_guide_is_public(): void
    {
        $this->get('/aprender/organizar-clientes')
            ->assertOk()
            ->assertSee('Como organizar seus clientes em um CRM')
            ->assertSee('Por que organizar clientes')
            ->assertSee('Como aplicar isso no Noobstron');
    }

    public function test_sales_guide_is_public(): void
    {
        $this->get('/aprender/processo-de-vendas')
            ->assertOk()
            ->assertSee('Como estruturar seu processo de vendas')
            ->assertSee('Entenda o processo comercial')
            ->assertSee('Como aplicar esse processo no Noobstron');
    }

    public function test_follow_up_guide_is_public(): void
    {
        $this->get('/aprender/follow-up-e-atividades')
            ->assertOk()
            ->assertSee(
                'Como melhorar seu follow-up e organizar atividades comerciais'
            )
            ->assertSee('Entenda o papel do follow-up')
            ->assertSee(
                'Como aplicar follow-up e atividades no Noobstron'
            );
    }

    public function test_communication_guide_is_public(): void
    {
        $this->get('/aprender/centralizar-comunicacao')
            ->assertOk()
            ->assertSee(
                'Como centralizar a comunicação com seus clientes'
            )
            ->assertSee(
                'Entenda o problema da comunicação espalhada'
            )
            ->assertSee(
                'Como aplicar comunicação centralizada no Noobstron'
            );
    }

    public function test_learning_pages_are_in_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/aprender', false)
            ->assertSee('/aprender/primeiros-passos', false)
            ->assertSee('/aprender/organizar-clientes', false)
            ->assertSee('/aprender/processo-de-vendas', false)
            ->assertSee('/aprender/follow-up-e-atividades', false)
            ->assertSee('/aprender/centralizar-comunicacao', false);
    }

    public function test_learning_center_links_to_published_guides(): void
    {
        $response = $this->get('/aprender');

        $response
            ->assertOk()
            ->assertSee(
                route('marketing.learn.getting-started'),
                false
            )
            ->assertSee(
                route('marketing.learn.customers'),
                false
            )
            ->assertSee(
                route('marketing.learn.sales'),
                false
            )
            ->assertSee(
                route('marketing.learn.follow-up'),
                false
            )
            ->assertSee(
                route('marketing.learn.communication'),
                false
            )
            ->assertSee('Abrir guia');
    }

    public function test_learning_translations_have_matching_keys(): void
    {
        $locales = [
            'pt-BR',
            'en',
            'es',
            'zh-CN',
            'ja',
        ];

        $flatten = function (
            array $array,
            string $prefix = ''
        ) use (&$flatten): array {
            $keys = [];

            foreach ($array as $key => $value) {
                $path = $prefix === ''
                    ? (string) $key
                    : $prefix . '.' . $key;

                if (is_array($value)) {
                    $keys = array_merge(
                        $keys,
                        $flatten($value, $path)
                    );
                } else {
                    $keys[] = $path;
                }
            }

            sort($keys);

            return $keys;
        };

        $base = require lang_path(
            'pt-BR/learn.php'
        );

        $baseKeys = $flatten($base);

        $this->assertCount(
            691,
            $baseKeys,
            'A quantidade de chaves do learn.php pt-BR mudou.'
        );

        foreach ($locales as $locale) {
            $data = require lang_path(
                $locale . '/learn.php'
            );

            $this->assertSame(
                $baseKeys,
                $flatten($data),
                "As chaves de learn.php diferem em {$locale}."
            );
        }
    }

    public function test_learning_center_renders_in_supported_locales(): void
    {
        $cases = [
            'pt-BR' => 'Central de Aprendizado Noobstron',
            'en' => 'Noobstron Learning Center',
            'es' => 'Centro de Aprendizaje Noobstron',
            'zh-CN' => 'Noobstron 学习中心',
            'ja' => 'Noobstron ラーニングセンター',
        ];

        foreach ($cases as $locale => $expected) {
            $this
                ->withHeader('Accept-Language', $locale)
                ->get('/aprender')
                ->assertOk()
                ->assertSee($expected);
        }
    }

    public function test_customers_guide_renders_in_supported_locales(): void
    {
        $cases = [
            'pt-BR' => 'Como organizar seus clientes em um CRM.',
            'en' => 'How to organize your customers in a CRM.',
            'es' => 'Cómo organizar tus clientes en un CRM.',
            'zh-CN' => '如何在 CRM 中整理客户。',
            'ja' => 'CRM で顧客情報を整理する方法。',
        ];

        foreach ($cases as $locale => $expected) {
            $this
                ->withHeader('Accept-Language', $locale)
                ->get('/aprender/organizar-clientes')
                ->assertOk()
                ->assertSee($expected);
        }
    }

    public function test_sales_guide_renders_in_supported_locales(): void
    {
        $cases = [
            'pt-BR' => 'Como estruturar seu processo de vendas.',
            'en' => 'How to structure your sales process.',
            'es' => 'Cómo estructurar tu proceso de ventas.',
            'zh-CN' => '如何构建销售流程。',
            'ja' => '営業プロセスを構築する方法。',
        ];

        foreach ($cases as $locale => $expected) {
            $this
                ->withHeader('Accept-Language', $locale)
                ->get('/aprender/processo-de-vendas')
                ->assertOk()
                ->assertSee($expected);
        }
    }

    public function test_follow_up_guide_renders_in_supported_locales(): void
    {
        $cases = [
            'pt-BR' =>
                'Como melhorar seu follow-up e organizar atividades comerciais.',

            'en' =>
                'How to improve follow-up and organize sales activities.',

            'es' =>
                'Cómo mejorar tu seguimiento y organizar actividades comerciales.',

            'zh-CN' =>
                '如何改进跟进并组织销售活动。',

            'ja' =>
                'フォローアップを改善し、営業活動を整理する方法。',
        ];

        foreach ($cases as $locale => $expected) {
            $this
                ->withHeader('Accept-Language', $locale)
                ->get('/aprender/follow-up-e-atividades')
                ->assertOk()
                ->assertSee($expected);
        }
    }

    public function test_communication_guide_renders_in_supported_locales(): void
    {
        $cases = [
            'pt-BR' =>
                'Como centralizar a comunicação com seus clientes.',

            'en' =>
                'How to centralize communication with your customers.',

            'es' =>
                'Cómo centralizar la comunicación con tus clientes.',

            'zh-CN' =>
                '如何集中管理与客户的沟通。',

            'ja' =>
                '顧客とのコミュニケーションを一元管理する方法。',
        ];

        foreach ($cases as $locale => $expected) {
            $this
                ->withHeader('Accept-Language', $locale)
                ->get('/aprender/centralizar-comunicacao')
                ->assertOk()
                ->assertSee($expected);
        }
    }
}