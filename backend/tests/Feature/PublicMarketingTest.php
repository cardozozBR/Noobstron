<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMarketingTest extends TestCase
{
    public function test_home_is_public_and_presents_the_product(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Noobstron')
            ->assertSee('Recursos')
            ->assertSee('Preços')
            ->assertSee('FAQ')
            ->assertSee('Contato')
            ->assertSee('Entrar');
    }

    public function test_home_has_basic_seo_metadata(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee(
                'name="description"',
                false
            )
            ->assertSee(
                'rel="canonical"',
                false
            );
    }

    public function test_authenticated_user_can_still_access_dashboard(): void
    {
        $routes = file_get_contents(
            base_path('routes/web.php')
        );

        $this->assertIsString($routes);

        $this->assertStringContainsString(
            "DashboardController::class",
            $routes
        );
    }

    public function test_home_presents_core_product_resources(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('CRM e clientes')
            ->assertSee('Leads e oportunidades')
            ->assertSee('Propostas e vendas')
            ->assertSee('Atividades e acompanhamento')
            ->assertSee('E-mail e WhatsApp')
            ->assertSee('Automação e IA');
    }

    public function test_home_presents_commercial_plans(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Start')
            ->assertSee('R$ 99')
            ->assertSee('Pro')
            ->assertSee('R$ 249')
            ->assertSee('Business')
            ->assertSee('R$ 499')
            ->assertSee('Enterprise')
            ->assertSee('Fale com a gente')
            ->assertSee('Mensal')
            ->assertSee('Anual');
    }

    public function test_home_answers_common_commercial_questions(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Para quem é a Noobstron?')
            ->assertSee('Preciso instalar alguma coisa?')
            ->assertSee('Posso mudar de plano depois?')
            ->assertSee('A plataforma funciona com WhatsApp e e-mail?')
            ->assertSee('Existe suporte a automações e IA?');
    }

    public function test_home_has_public_contact_form(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Fale com nossa equipe')
            ->assertSee('Nome')
            ->assertSee('E-mail')
            ->assertSee('Empresa')
            ->assertSee('Mensagem')
            ->assertSee(
                'name="name"',
                false
            )
            ->assertSee(
                'name="email"',
                false
            )
            ->assertSee(
                'name="company"',
                false
            )
            ->assertSee(
                'name="message"',
                false
            )
            ->assertSee(
                'method="POST"',
                false
            )
            ->assertSee('Enviar mensagem');
    }

    public function test_home_has_social_seo_metadata(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('property="og:type"', false)
            ->assertSee('property="og:url"', false)
            ->assertSee('name="twitter:card"', false);
    }

    public function test_public_robots_references_sitemap(): void
    {
        $robots = $this->get('/robots.txt');

        $robots
            ->assertOk()
            ->assertSee('User-agent: *', false)
            ->assertSee('Sitemap:', false)
            ->assertSee('/sitemap.xml', false);

        $sitemap = $this->get('/sitemap.xml');

        $sitemap
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee('<loc>', false)
            ->assertSee(url('/'), false);
    }

    public function test_home_links_visitors_to_self_service_registration(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Começar trial')
            ->assertSee(
                'href="/register"',
                false
            );
    }

    public function test_home_hero_invites_visitor_to_start_trial(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $heroStart = strpos(
            $html,
            '<section class="hero">'
        );

        $this->assertNotFalse($heroStart);

        $heroEnd = strpos(
            $html,
            '</section>',
            $heroStart
        );

        $this->assertNotFalse($heroEnd);

        $hero = substr(
            $html,
            $heroStart,
            ($heroEnd - $heroStart)
        );

        $this->assertStringContainsString(
            'Começar trial',
            $hero
        );

        $this->assertStringContainsString(
            'href="/register"',
            $hero
        );

        $this->assertStringContainsString(
            'Entrar',
            $hero
        );
    }

    public function test_self_service_plan_cards_link_to_registration(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        foreach ([
            'Start',
            'Pro',
            'Business',
        ] as $planName) {
            $pattern =
                '/<article\b[^>]*>' .
                '(?:(?!<\/article>).)*' .
                '<h3\b[^>]*>\s*' .
                preg_quote($planName, '/') .
                '\s*<\/h3>' .
                '(?:(?!<\/article>).)*' .
                '<\/article>/su';

            $matched = preg_match(
                $pattern,
                $html,
                $matches
            );

            $this->assertSame(
                1,
                $matched,
                "Card do plano {$planName} nao encontrado."
            );

            $card = $matches[0];

            $this->assertStringContainsString(
                'href="/register"',
                $card,
                "Plano {$planName} deveria apontar para /register."
            );

            $this->assertStringContainsString(
                'Começar trial',
                $card,
                "Plano {$planName} deveria exibir CTA de trial."
            );
        }
    }

    public function test_enterprise_plan_remains_commercial(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $pattern =
            '/<article\b[^>]*>' .
            '(?:(?!<\/article>).)*' .
            '<h3\b[^>]*>\s*Enterprise\s*<\/h3>' .
            '(?:(?!<\/article>).)*' .
            '<\/article>/su';

        $matched = preg_match(
            $pattern,
            $html,
            $matches
        );

        $this->assertSame(
            1,
            $matched,
            'Card Enterprise nao encontrado.'
        );

        $card = $matches[0];

        $this->assertStringContainsString(
            'Fale com a gente',
            $card
        );

        $this->assertStringNotContainsString(
            'href="/register"',
            $card
        );
    }

    public function test_contact_section_remains_commercial_without_self_service_ctas(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $contactStart = strpos(
            $html,
            '<section id="contato"'
        );

        $this->assertNotFalse($contactStart);

        $contactEnd = strpos(
            $html,
            '</section>',
            $contactStart
        );

        $this->assertNotFalse($contactEnd);

        $contact = substr(
            $html,
            $contactStart,
            ($contactEnd - $contactStart)
        );

        $this->assertStringContainsString(
            'Contato',
            $contact
        );

        $this->assertStringContainsString(
            'Formulário de contato comercial',
            $contact
        );

        $this->assertStringContainsString(
            'Enviar mensagem',
            $contact
        );

        $this->assertStringNotContainsString(
            'Criar conta',
            $contact
        );

        $this->assertStringNotContainsString(
            'Começar trial',
            $contact
        );

        $this->assertStringNotContainsString(
            'href="/register"',
            $contact
        );
    }

    public function test_self_service_plan_ctas_use_consistent_button_style(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        foreach ([
            'Start',
            'Pro',
            'Business',
        ] as $planName) {
            $pattern =
                '/<article\b[^>]*>' .
                '(?:(?!<\/article>).)*' .
                '<h3\b[^>]*>\s*' .
                preg_quote($planName, '/') .
                '\s*<\/h3>' .
                '(?:(?!<\/article>).)*' .
                '<\/article>/su';

            $matched = preg_match(
                $pattern,
                $html,
                $matches
            );

            $this->assertSame(
                1,
                $matched,
                "Card {$planName} nao encontrado."
            );

            $card = $matches[0];

            $this->assertStringContainsString(
                'class="button',
                $card,
                "CTA {$planName} deveria usar o componente visual button."
            );
        }

        $this->assertStringContainsString(
            'Mais completo para crescer',
            $html
        );
    }

    public function test_pricing_section_uses_marketing_layout_without_duplicate_section(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, '<section id="precos"')
        );

        $this->assertStringNotContainsString(
            '<section id="pricing"',
            $html
        );

        $pricingStart = strpos($html, '<section id="precos"');
        $this->assertNotFalse($pricingStart);

        $pricingEnd = strpos($html, '</section>', $pricingStart);
        $this->assertNotFalse($pricingEnd);

        $pricing = substr(
            $html,
            $pricingStart,
            ($pricingEnd - $pricingStart)
        );

        $this->assertStringContainsString(
            'class="pricing-grid"',
            $pricing
        );

        $this->assertStringContainsString(
            'class="pricing-card pricing-card--featured"',
            $pricing
        );

        foreach (['Start', 'Pro', 'Business', 'Enterprise'] as $plan) {
            $this->assertStringContainsString($plan, $pricing);
        }
    }

    public function test_contact_form_uses_marketing_layout_shell(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $contactStart = strpos($html, '<section id="contato"');
        $this->assertNotFalse($contactStart);

        $contactEnd = strpos($html, '</section>', $contactStart);
        $this->assertNotFalse($contactEnd);

        $contact = substr(
            $html,
            $contactStart,
            ($contactEnd - $contactStart)
        );

        $shellStart = strpos($contact, '<div class="shell">');
        $formStart = strpos($contact, '<form');
        $shellEnd = strrpos($contact, '</div>');

        $this->assertNotFalse($shellStart);
        $this->assertNotFalse($formStart);
        $this->assertNotFalse($shellEnd);
        $this->assertGreaterThan($shellStart, $formStart);
        $this->assertGreaterThan($formStart, $shellEnd);

        $this->assertStringContainsString(
            'class="contact-form__grid"',
            $contact
        );
    }

    public function test_marketing_layout_defines_pricing_and_contact_components(): void
    {
        $layout = file_get_contents(
            resource_path('views/layouts/marketing.blade.php')
        );

        $this->assertIsString($layout);

        foreach ([
            '.pricing-grid',
            '.pricing-card',
            '.pricing-card--featured',
            '.contact-form',
            '.contact-form__grid',
            '.contact-form input',
            '.contact-form textarea',
        ] as $selector) {
            $this->assertStringContainsString(
                $selector,
                $layout
            );
        }
    }

}
