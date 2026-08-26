<?php

namespace Tests\Feature;

use App\Enums\CommercialContactStatus;
use App\Models\CommercialContact;
use App\Models\PlatformAdmin;
use App\Models\Tenant;
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


    public function test_platform_admin_can_convert_commercial_contact_to_existing_tenant(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Convertido',
            'slug' => 'tenant-convertido',
            'status' => 'active',
        ]);

        $contact = CommercialContact::query()->create([
            'name' => 'Contato para conversão',
            'email' => 'conversion@example.test',
            'message' => 'Converter este contato.',
            'status' => CommercialContactStatus::QUALIFIED,
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->patch(
                route(
                    'platform.contacts.convert',
                    $contact
                ),
                [
                    'tenant_id' => $tenant->id,
                ]
            );

        $response
            ->assertRedirect(
                route('platform.contacts.index')
            )
            ->assertSessionHas(
                'success',
                __('platform.contacts.converted')
            );

        $contact->refresh();

        $this->assertSame(
            CommercialContactStatus::CONVERTED,
            $contact->status
        );

        $this->assertSame(
            $tenant->id,
            $contact->converted_tenant_id
        );

        $this->assertNotNull(
            $contact->converted_at
        );

        $this->assertTrue(
            $contact->convertedTenant->is($tenant)
        );
    }

    public function test_commercial_contact_conversion_is_audited(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Auditado',
            'slug' => 'tenant-auditado',
            'status' => 'active',
        ]);

        $contact = CommercialContact::query()->create([
            'name' => 'Contato auditado',
            'email' => 'audit@example.test',
            'message' => 'Auditar conversão.',
            'status' => CommercialContactStatus::QUALIFIED,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->patch(
                route(
                    'platform.contacts.convert',
                    $contact
                ),
                [
                    'tenant_id' => $tenant->id,
                ]
            )
            ->assertRedirect(
                route('platform.contacts.index')
            );

        $this->assertDatabaseHas(
            'platform_admin_audit_logs',
            [
                'platform_admin_id' => $admin->id,
                'tenant_id' => $tenant->id,
                'action' => 'commercial_contact.converted',
                'entity_type' => CommercialContact::class,
                'entity_id' => (string) $contact->id,
                'result' => 'success',
            ]
        );
    }

    public function test_invalid_tenant_cannot_be_used_for_commercial_contact_conversion(): void
    {
        $admin = $this->platformAdmin();

        $contact = CommercialContact::query()->create([
            'name' => 'Contato inválido',
            'email' => 'invalid-tenant@example.test',
            'message' => 'Tenant inválido.',
            'status' => CommercialContactStatus::QUALIFIED,
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->from(
                route('platform.contacts.index')
            )
            ->patch(
                route(
                    'platform.contacts.convert',
                    $contact
                ),
                [
                    'tenant_id' => 999999,
                ]
            )
            ->assertRedirect(
                route('platform.contacts.index')
            )
            ->assertSessionHasErrors(
                'tenant_id'
            );

        $contact->refresh();

        $this->assertSame(
            CommercialContactStatus::QUALIFIED,
            $contact->status
        );

        $this->assertNull(
            $contact->converted_tenant_id
        );

        $this->assertNull(
            $contact->converted_at
        );
    }

    public function test_guest_cannot_convert_commercial_contact(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant Protegido',
            'slug' => 'tenant-protegido',
            'status' => 'active',
        ]);

        $contact = CommercialContact::query()->create([
            'name' => 'Contato protegido',
            'email' => 'protected-convert@example.test',
            'message' => 'Teste de autorização.',
            'status' => CommercialContactStatus::QUALIFIED,
        ]);

        $this
            ->patch(
                route(
                    'platform.contacts.convert',
                    $contact
                ),
                [
                    'tenant_id' => $tenant->id,
                ]
            )
            ->assertRedirect(
                route('platform.login')
            );

        $contact->refresh();

        $this->assertSame(
            CommercialContactStatus::QUALIFIED,
            $contact->status
        );

        $this->assertNull(
            $contact->converted_tenant_id
        );
    }

    public function test_platform_contacts_page_shows_converted_tenant(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Visível',
            'slug' => 'tenant-visivel',
            'status' => 'active',
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato convertido',
            'email' => 'visible-converted@example.test',
            'message' => 'Contato convertido.',
            'status' => CommercialContactStatus::CONVERTED,
            'converted_tenant_id' => $tenant->id,
            'converted_at' => now(),
        ]);

        $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.contacts.index')
            )
            ->assertOk()
            ->assertSee('Tenant Visível')
            ->assertSee(
                route(
                    'platform.tenants.show',
                    $tenant
                ),
                false
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
