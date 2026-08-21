<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_whatsapp(): void
    {
        $tenant = $this->tenant(
            'whatsapp-http-guest'
        );

        $this->get(
            "http://{$tenant->slug}.localhost/whatsapp"
        )
            ->assertRedirect(
                route('login')
            );
    }

    public function test_whatsapp_requires_feature(): void
    {
        $tenant = $this->tenant(
            'whatsapp-http-feature'
        );

        $user = $this->user(
            $tenant
        );

        $this->actingAs(
            $user
        );

        $this->get(
            "http://{$tenant->slug}.localhost/whatsapp"
        )
            ->assertForbidden();
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' =>
                    'Tenant ' . $slug,

                'slug' =>
                    $slug,

                'status' =>
                    'active',

                'country_code' =>
                    'BR',

                'locale' =>
                    'pt-BR',

                'timezone' =>
                    'America/Fortaleza',

                'currency' =>
                    'BRL',
            ]);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant
    ): User {
        return User::factory()
            ->create([
                'tenant_id' =>
                    $tenant->id,
            ]);
    }
}