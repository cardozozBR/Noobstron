<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRegistrationLocaleTest extends TestCase
{
    public function test_english_registration_page_is_translated(): void
    {
        $this
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('http://localhost/register')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Company')
            ->assertSee('Password')
            ->assertSee('Country')
            ->assertSee('Plan')
            ->assertSee('Order summary')
            ->assertSee('Already have an account?')
            ->assertSee(route('workspace.login'), false);
    }

    public function test_spanish_registration_page_is_translated(): void
    {
        $this
            ->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('http://localhost/register')
            ->assertOk()
            ->assertSee('Crear tu cuenta')
            ->assertSee('Empresa')
            ->assertSee('Contraseña')
            ->assertSee('País')
            ->assertSee('Resumen de contratación');
    }

    public function test_chinese_registration_page_is_translated(): void
    {
        $this
            ->withHeader('Accept-Language', 'zh-CN,zh;q=0.9')
            ->get('http://localhost/register')
            ->assertOk()
            ->assertSee('创建账户')
            ->assertSee('公司')
            ->assertSee('密码')
            ->assertSee('国家')
            ->assertSee('订单摘要');
    }

    public function test_japanese_registration_page_is_translated(): void
    {
        $this
            ->withHeader('Accept-Language', 'ja-JP,ja;q=0.9')
            ->get('http://localhost/register')
            ->assertOk()
            ->assertSee('アカウントを作成')
            ->assertSee('会社')
            ->assertSee('パスワード')
            ->assertSee('国')
            ->assertSee('申込内容');
    }
}