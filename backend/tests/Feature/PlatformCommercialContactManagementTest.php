<?php

namespace Tests\Feature;

use App\Enums\CommercialContactStatus;
use App\Models\CommercialContact;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformCommercialContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_list_commercial_contacts_with_status(): void
    {
        $admin = $this->platformAdmin();

        CommercialContact::query()->create([
            'name' => 'Lead Novo',
            'email' => 'novo@example.test',
            'company' => 'Empresa Nova',
            'message' => 'Quero conhecer a plataforma.',
            'status' => CommercialContactStatus::NEW,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.contacts.index')
            )
            ->assertOk()
            ->assertSee('Lead Novo')
            ->assertSee('Empresa Nova')
            ->assertSee(
                CommercialContactStatus::NEW->label()
            );
    }

    public function test_platform_admin_can_filter_commercial_contacts_by_status(): void
    {
        $admin = $this->platformAdmin();

        CommercialContact::query()->create([
            'name' => 'Contato Novo',
            'email' => 'novo@example.test',
            'message' => 'Novo contato.',
            'status' => CommercialContactStatus::NEW,
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato Qualificado',
            'email' => 'qualified@example.test',
            'message' => 'Contato qualificado.',
            'status' => CommercialContactStatus::QUALIFIED,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(
                route(
                    'platform.contacts.index',
                    [
                        'status' =>
                            CommercialContactStatus::QUALIFIED->value,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Contato Qualificado')
            ->assertDontSee('Contato Novo');
    }

    public function test_invalid_commercial_contact_status_filter_is_ignored(): void
    {
        $admin = $this->platformAdmin();

        CommercialContact::query()->create([
            'name' => 'Contato Visível',
            'email' => 'visible@example.test',
            'message' => 'Contato.',
            'status' => CommercialContactStatus::NEW,
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get(
                route(
                    'platform.contacts.index',
                    [
                        'status' => 'invalid-status',
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSee('Contato Visível')
            ->assertViewHas(
                'status',
                ''
            );
    }

    public function test_platform_admin_can_update_commercial_contact_status(): void
    {
        $admin = $this->platformAdmin();

        $contact = CommercialContact::query()->create([
            'name' => 'Lead para contato',
            'email' => 'update@example.test',
            'message' => 'Atualizar status.',
            'status' => CommercialContactStatus::NEW,
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->patch(
                route(
                    'platform.contacts.status.update',
                    $contact
                ),
                [
                    'status' =>
                        CommercialContactStatus::CONTACTED->value,
                ]
            );

        $response
            ->assertRedirect(
                route('platform.contacts.index')
            )
            ->assertSessionHas(
                'success',
                __('platform.contacts.status_updated')
            );

        $this->assertSame(
            CommercialContactStatus::CONTACTED,
            $contact->refresh()->status
        );
    }

    public function test_invalid_commercial_contact_status_update_is_rejected(): void
    {
        $admin = $this->platformAdmin();

        $contact = CommercialContact::query()->create([
            'name' => 'Lead inválido',
            'email' => 'invalid@example.test',
            'message' => 'Teste inválido.',
            'status' => CommercialContactStatus::NEW,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->from(
                route('platform.contacts.index')
            )
            ->patch(
                route(
                    'platform.contacts.status.update',
                    $contact
                ),
                [
                    'status' => 'invalid-status',
                ]
            )
            ->assertRedirect(
                route('platform.contacts.index')
            )
            ->assertSessionHasErrors(
                'status'
            );

        $this->assertSame(
            CommercialContactStatus::NEW,
            $contact->refresh()->status
        );
    }

    public function test_guest_cannot_update_commercial_contact_status(): void
    {
        $contact = CommercialContact::query()->create([
            'name' => 'Lead protegido',
            'email' => 'protected@example.test',
            'message' => 'Teste de autorização.',
            'status' => CommercialContactStatus::NEW,
        ]);

        $this
            ->patch(
                route(
                    'platform.contacts.status.update',
                    $contact
                ),
                [
                    'status' =>
                        CommercialContactStatus::CONTACTED->value,
                ]
            )
            ->assertRedirect(
                route('platform.login')
            );

        $this->assertSame(
            CommercialContactStatus::NEW,
            $contact->refresh()->status
        );
    }

    private function platformAdmin(): PlatformAdmin
    {
        return PlatformAdmin::query()->create([
            'name' => 'Commercial Master',
            'email' => 'commercial-master@example.test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }
}