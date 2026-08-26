<?php

namespace Tests\Feature;

use App\Enums\CommercialContactStatus;
use App\Models\CommercialContact;
use App\Models\PlatformAdmin;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
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

    public function test_platform_contacts_page_shows_latest_contracted_plan(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant com Plano',
            'slug' => 'tenant-com-plano',
            'status' => 'active',
        ]);

        $oldPlan = Plan::query()->create([
            'code' => 'commercial-old',
            'name' => 'Plano Antigo',
            'active' => true,
        ]);

        $currentPlan = Plan::query()->create([
            'code' => 'commercial-pro',
            'name' => 'Plano Pro Comercial',
            'active' => true,
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $oldPlan->id,
            'status' => 'cancelled',
            'current_period_start' => now()->subMonths(2),
            'current_period_end' => now()->subMonth(),
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $currentPlan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato com plano',
            'email' => 'contracted-plan@example.test',
            'company' => 'Empresa com plano',
            'message' => 'Contato convertido com assinatura.',
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
            ->assertSee(
                __('platform.contacts.contracted_plan')
            )
            ->assertSee('Plano Pro Comercial')
            ->assertDontSee('Plano Antigo');
    }

    public function test_platform_contacts_page_handles_converted_tenant_without_subscription(): void
    {
        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Sem Assinatura',
            'slug' => 'tenant-sem-assinatura',
            'status' => 'active',
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato sem assinatura',
            'email' => 'without-subscription@example.test',
            'company' => 'Empresa sem assinatura',
            'message' => 'Contato sem plano contratado.',
            'status' => CommercialContactStatus::CONVERTED,
            'converted_tenant_id' => $tenant->id,
            'converted_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'platform')
            ->get(
                route('platform.contacts.index')
            );

        $response
            ->assertOk()
            ->assertSee('Tenant Sem Assinatura')
            ->assertSee(
                __('platform.contacts.contracted_plan')
            );

        $contact = CommercialContact::query()
            ->where(
                'email',
                'without-subscription@example.test'
            )
            ->firstOrFail();

        $this->assertNull(
            $contact
                ->convertedTenant
                ->latestSubscription
        );
    }

    public function test_platform_contacts_page_shows_subscription_snapshot_revenue(): void
    {
        app()->setLocale('pt-BR');

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Receita Snapshot',
            'slug' => 'tenant-receita-snapshot',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $plan = Plan::query()->create([
            'code' => 'commercial-revenue-snapshot',
            'name' => 'Plano Receita Snapshot',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 99900,
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'currency' => 'BRL',
            'amount_minor' => 19900,
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato receita snapshot',
            'email' => 'revenue-snapshot@example.test',
            'message' => 'Receita pelo snapshot.',
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
            ->assertSee(
                __('platform.contacts.revenue')
            )
            ->assertSee('Plano Receita Snapshot')
            ->assertSee('199,00')
            ->assertDontSee('999,00');
    }

    public function test_platform_contacts_page_uses_plan_price_when_subscription_snapshot_is_missing(): void
    {
        app()->setLocale('pt-BR');

        $admin = $this->platformAdmin();

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Receita Fallback',
            'slug' => 'tenant-receita-fallback',
            'status' => 'active',
            'currency' => 'BRL',
        ]);

        $plan = Plan::query()->create([
            'code' => 'commercial-revenue-fallback',
            'name' => 'Plano Receita Fallback',
            'active' => true,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'currency' => 'BRL',
            'amount_minor' => 29900,
        ]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'currency' => null,
            'amount_minor' => null,
        ]);

        CommercialContact::query()->create([
            'name' => 'Contato receita fallback',
            'email' => 'revenue-fallback@example.test',
            'message' => 'Receita usando preço do plano.',
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
            ->assertSee('Plano Receita Fallback')
            ->assertSee('299,00');
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
