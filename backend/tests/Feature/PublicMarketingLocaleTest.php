<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMarketingLocaleTest extends TestCase
{
    public function test_english_home_translates_remaining_marketing_sections(): void
    {
        $this
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('Customer management')
            ->assertSee('A plan for every stage of your business')
            ->assertSee('Do I need to install anything?')
            ->assertSee('Talk to our team')
            ->assertSee('Send message');
    }

    public function test_spanish_home_translates_remaining_marketing_sections(): void
    {
        $this
            ->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('Gestión de clientes')
            ->assertSee('Un plan para cada etapa de tu negocio')
            ->assertSee('¿Necesito instalar algo?')
            ->assertSee('Habla con nuestro equipo');
    }

    public function test_chinese_home_translates_remaining_marketing_sections(): void
    {
        $this
            ->withHeader('Accept-Language', 'zh-CN,zh;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('客户管理')
            ->assertSee('适合企业每个阶段的方案')
            ->assertSee('需要安装任何软件吗？');
    }

    public function test_japanese_home_translates_remaining_marketing_sections(): void
    {
        $this
            ->withHeader('Accept-Language', 'ja-JP,ja;q=0.9')
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee('顧客管理')
            ->assertSee('ビジネスの各段階に合うプラン')
            ->assertSee('インストールは必要ですか？');
    }
}