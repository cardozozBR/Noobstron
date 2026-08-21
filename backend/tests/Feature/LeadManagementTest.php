<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Permission as PermissionEnum;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $model->id
            );
    }

    private function payload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' => 'Lead Principal',
                'email' => 'lead@example.local',
                'phone' => '+5511999999999',
                'status' => LeadStatus::NEW->value,
                'source' => LeadSource::MANUAL->value,
                'responsible_user_id' => null,
                'tags' => [
                    'vip',
                    'evento',
                ],
                'notes' => 'Observação inicial.',
            ],
            $overrides
        );
    }

    public function test_user_with_permission_can_list_leads(): void
    {
        $tenant = $this->tenant(
            'leads-list'
        );

        $user = $this->user(
            $tenant,
            'list@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        Lead::create([
            'name' => 'Lead Visível',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-list.localhost/leads'
            );

        $response->assertOk();
        $response->assertSee(
            'Lead Visível'
        );
    }

    public function test_user_without_permission_cannot_list_leads(): void
    {
        $tenant = $this->tenant(
            'leads-no-permission'
        );

        $user = $this->user(
            $tenant,
            'denied@leads.local'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-no-permission.localhost/leads'
            );

        $response->assertForbidden();
    }

    public function test_user_is_blocked_when_leads_feature_is_disabled(): void
    {
        $tenant = $this->tenant(
            'leads-feature-off'
        );

        $user = $this->user(
            $tenant,
            'feature-off@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-feature-off.localhost/leads'
            );

        $response->assertForbidden();
    }

    public function test_lead_can_be_created(): void
    {
        $tenant = $this->tenant(
            'leads-create'
        );

        $user = $this->user(
            $tenant,
            'create@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://leads-create.localhost/leads',
                $this->payload()
            );

        $response->assertRedirect(
            route('leads.index')
        );

        $this->assertDatabaseHas(
            'leads',
            [
                'tenant_id' => $tenant->id,
                'name' => 'Lead Principal',
                'status' => 'new',
                'source' => 'manual',
            ]
        );
    }

    public function test_lead_creation_is_audited(): void
    {
        $tenant = $this->tenant(
            'leads-create-audit'
        );

        $user = $this->user(
            $tenant,
            'audit-create@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                'http://leads-create-audit.localhost/leads',
                $this->payload()
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'lead.created',
            ]
        );
    }

    public function test_lead_can_be_updated(): void
    {
        $tenant = $this->tenant(
            'leads-update'
        );

        $user = $this->user(
            $tenant,
            'update@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Antes',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "http://leads-update.localhost/leads/{$lead->id}",
                $this->payload([
                    'name' => 'Depois',
                    'status' => LeadStatus::QUALIFIED->value,
                    'source' => LeadSource::REFERRAL->value,
                    'notes' => 'Atualizado.',
                ])
            );

        $response->assertRedirect(
            route('leads.index')
        );

        $lead->refresh();

        $this->assertSame(
            'Depois',
            $lead->name
        );

        $this->assertSame(
            LeadStatus::QUALIFIED,
            $lead->status
        );

        $this->assertSame(
            LeadSource::REFERRAL,
            $lead->source
        );

        $this->assertSame(
            'Atualizado.',
            $lead->notes
        );
    }

    public function test_lead_update_is_audited(): void
    {
        $tenant = $this->tenant(
            'leads-update-audit'
        );

        $user = $this->user(
            $tenant,
            'audit-update@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Original',
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://leads-update-audit.localhost/leads/{$lead->id}",
                $this->payload([
                    'name' => 'Alterado',
                ])
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'lead.updated',
            ]
        );
    }

    public function test_lead_can_be_deleted(): void
    {
        $tenant = $this->tenant(
            'leads-delete'
        );

        $user = $this->user(
            $tenant,
            'delete@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_DELETE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Excluir',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://leads-delete.localhost/leads/{$lead->id}"
            );

        $response->assertRedirect(
            route('leads.index')
        );

        $this->assertDatabaseMissing(
            'leads',
            [
                'id' => $lead->id,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'lead.deleted',
            ]
        );
    }

    public function test_lead_cannot_use_responsible_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'leads-owner-a'
        );

        $tenantB = $this->tenant(
            'leads-owner-b'
        );

        $userA = $this->user(
            $tenantA,
            'creator@owner-a.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::LEADS_CREATE
        );

        $userB = $this->user(
            $tenantB,
            'responsible@owner-b.local'
        );

        $response = $this
            ->actingAs($userA)
            ->from(
                'http://leads-owner-a.localhost/leads/create'
            )
            ->post(
                'http://leads-owner-a.localhost/leads',
                $this->payload([
                    'responsible_user_id' => $userB->id,
                ])
            );

        $response->assertSessionHasErrors(
            'responsible_user_id'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            0,
            Lead::query()->count()
        );
    }

    public function test_lead_from_another_tenant_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'leads-update-a'
        );

        $tenantB = $this->tenant(
            'leads-update-b'
        );

        $userA = $this->user(
            $tenantA,
            'update-a@leads.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB = Lead::create([
            'name' => 'Lead B',
        ]);

        $response = $this
            ->actingAs($userA)
            ->put(
                "http://leads-update-a.localhost/leads/{$leadB->id}",
                $this->payload()
            );

        $response->assertNotFound();

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB->refresh();

        $this->assertSame(
            'Lead B',
            $leadB->name
        );
    }

    public function test_lead_from_another_tenant_cannot_be_deleted(): void
    {
        $tenantA = $this->tenant(
            'leads-delete-a'
        );

        $tenantB = $this->tenant(
            'leads-delete-b'
        );

        $userA = $this->user(
            $tenantA,
            'delete-a@leads.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::LEADS_DELETE
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB = Lead::create([
            'name' => 'Lead B',
        ]);

        $response = $this
            ->actingAs($userA)
            ->delete(
                "http://leads-delete-a.localhost/leads/{$leadB->id}"
            );

        $response->assertNotFound();

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertTrue(
            Lead::query()
                ->whereKey($leadB->id)
                ->exists()
        );
    }

    public function test_status_and_source_must_be_valid(): void
    {
        $tenant = $this->tenant(
            'leads-validation'
        );

        $user = $this->user(
            $tenant,
            'validation@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->from(
                'http://leads-validation.localhost/leads/create'
            )
            ->post(
                'http://leads-validation.localhost/leads',
                $this->payload([
                    'status' => 'invalid',
                    'source' => 'invalid',
                ])
            );

        $response->assertSessionHasErrors([
            'status',
            'source',
        ]);
    }

    public function test_tags_are_normalized(): void
    {
        $tenant = $this->tenant(
            'leads-tags'
        );

        $user = $this->user(
            $tenant,
            'tags@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                'http://leads-tags.localhost/leads',
                $this->payload([
                    'tags' => [
                        ' vip ',
                        'evento',
                        'vip',
                    ],
                ])
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::query()
            ->firstOrFail();

        $this->assertSame(
            [
                'vip',
                'evento',
            ],
            $lead->tags
        );
    }

    public function test_user_with_permission_can_open_lead_create_form(): void
    {
        $tenant = $this->tenant(
            'leads-create-form'
        );

        $user = $this->user(
            $tenant,
            'create-form@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-create-form.localhost/leads/create'
            );

        $response->assertOk();
        $response->assertSee(
            'Novo lead'
        );
    }

    public function test_user_with_permission_can_open_lead_edit_form(): void
    {
        $tenant = $this->tenant(
            'leads-edit-form'
        );

        $user = $this->user(
            $tenant,
            'edit-form@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead para editar',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                "http://leads-edit-form.localhost/leads/{$lead->id}/edit"
            );

        $response->assertOk();
        $response->assertSee(
            'Lead para editar'
        );
    }

    public function test_lead_edit_form_cannot_open_lead_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'leads-edit-view-a'
        );

        $tenantB = $this->tenant(
            'leads-edit-view-b'
        );

        $userA = $this->user(
            $tenantA,
            'edit-view-a@leads.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB = Lead::create([
            'name' => 'Lead secreto B',
        ]);

        $response = $this
            ->actingAs($userA)
            ->get(
                "http://leads-edit-view-a.localhost/leads/{$leadB->id}/edit"
            );

        $response->assertNotFound();
    }

    public function test_lead_list_can_be_filtered_by_status(): void
    {
        $tenant = $this->tenant('leads-filter-status');
        $user = $this->user(
            $tenant,
            'filter-status@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        Lead::create([
            'name' => 'Lead Novo',
            'status' => LeadStatus::NEW,
        ]);

        Lead::create([
            'name' => 'Lead Qualificado',
            'status' => LeadStatus::QUALIFIED,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-filter-status.localhost/leads?status=qualified'
            );

        $response->assertOk();
        $response->assertSee('Lead Qualificado');
        $response->assertDontSee('Lead Novo');
    }

    public function test_lead_list_can_be_filtered_by_source(): void
    {
        $tenant = $this->tenant('leads-filter-source');
        $user = $this->user(
            $tenant,
            'filter-source@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        Lead::create([
            'name' => 'Lead Site',
            'source' => LeadSource::WEBSITE,
        ]);

        Lead::create([
            'name' => 'Lead Manual',
            'source' => LeadSource::MANUAL,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-filter-source.localhost/leads?source=website'
            );

        $response->assertOk();
        $response->assertSee('Lead Site');
        $response->assertDontSee('Lead Manual');
    }

    public function test_lead_list_can_be_filtered_by_responsible(): void
    {
        $tenant = $this->tenant('leads-filter-owner');

        $user = $this->user(
            $tenant,
            'filter-owner@leads.local'
        );

        $owner = $this->user(
            $tenant,
            'owner@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        Lead::create([
            'name' => 'Lead Com Owner',
            'responsible_user_id' => $owner->id,
        ]);

        Lead::create([
            'name' => 'Lead Sem Owner',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-filter-owner.localhost/leads?responsible_user_id='
                . $owner->id
            );

        $response->assertOk();
        $response->assertSee('Lead Com Owner');
        $response->assertDontSee('Lead Sem Owner');
    }

    public function test_lead_list_searches_name_email_and_phone(): void
    {
        $tenant = $this->tenant('leads-search');

        $user = $this->user(
            $tenant,
            'search@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        Lead::create([
            'name' => 'Empresa Alfa',
            'email' => 'alfa@example.local',
            'phone' => '111111111',
        ]);

        Lead::create([
            'name' => 'Empresa Beta',
            'email' => 'beta@example.local',
            'phone' => '222222222',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-search.localhost/leads?search=alfa'
            );

        $response->assertOk();
        $response->assertSee('Empresa Alfa');
        $response->assertDontSee('Empresa Beta');
    }

    public function test_lead_list_is_paginated(): void
    {
        $tenant = $this->tenant('leads-pagination');

        $user = $this->user(
            $tenant,
            'pagination@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        for ($i = 1; $i <= 21; $i++) {
            Lead::create([
                'name' => sprintf(
                    'Lead %02d',
                    $i
                ),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-pagination.localhost/leads'
            );

        $response->assertOk();

        $response->assertViewHas(
            'leads',
            fn ($leads) =>
                $leads->perPage() === 20
                && $leads->total() === 21
        );
    }

    public function test_lead_name_is_required(): void
    {
        $tenant = $this->tenant('leads-name-required');

        $user = $this->user(
            $tenant,
            'name-required@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->from(
                'http://leads-name-required.localhost/leads/create'
            )
            ->post(
                'http://leads-name-required.localhost/leads',
                $this->payload([
                    'name' => '',
                ])
            );

        $response->assertSessionHasErrors('name');
    }

    public function test_lead_email_must_be_valid(): void
    {
        $tenant = $this->tenant(
            'leads-email-validation'
        );

        $user = $this->user(
            $tenant,
            'email-validation@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->from(
                'http://leads-email-validation.localhost/leads/create'
            )
            ->post(
                'http://leads-email-validation.localhost/leads',
                $this->payload([
                    'email' => 'invalid',
                ])
            );

        $response->assertSessionHasErrors('email');
    }

    public function test_create_route_is_blocked_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant('leads-create-off');

        $user = $this->user(
            $tenant,
            'create-off@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://leads-create-off.localhost/leads/create'
            );

        $response->assertForbidden();
    }

    public function test_store_is_blocked_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant('leads-store-off');

        $user = $this->user(
            $tenant,
            'store-off@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_CREATE
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://leads-store-off.localhost/leads',
                $this->payload()
            );

        $response->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertSame(
            0,
            Lead::query()->count()
        );
    }

    public function test_update_is_blocked_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant('leads-update-off');

        $user = $this->user(
            $tenant,
            'update-off@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_UPDATE
        );

        app(TenantContext::class)->set($tenant);

        $lead = Lead::create([
            'name' => 'Original',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->put(
                "http://leads-update-off.localhost/leads/{$lead->id}",
                $this->payload([
                    'name' => 'Alterado',
                ])
            );

        $response->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $lead->refresh();

        $this->assertSame(
            'Original',
            $lead->name
        );
    }

    public function test_delete_is_blocked_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant('leads-delete-off');

        $user = $this->user(
            $tenant,
            'delete-off@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_DELETE
        );

        app(TenantContext::class)->set($tenant);

        $lead = Lead::create([
            'name' => 'Preservado',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::LEADS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://leads-delete-off.localhost/leads/{$lead->id}"
            );

        $response->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            Lead::query()
                ->whereKey($lead->id)
                ->exists()
        );
    }

    public function test_create_requires_create_permission(): void
    {
        $tenant = $this->tenant(
            'leads-create-permission'
        );

        $user = $this->user(
            $tenant,
            'create-permission@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://leads-create-permission.localhost/leads',
                $this->payload()
            );

        $response->assertForbidden();
    }

    public function test_update_requires_update_permission(): void
    {
        $tenant = $this->tenant(
            'leads-update-permission'
        );

        $user = $this->user(
            $tenant,
            'update-permission@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        $lead = Lead::create([
            'name' => 'Original',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "http://leads-update-permission.localhost/leads/{$lead->id}",
                $this->payload()
            );

        $response->assertForbidden();
    }

    public function test_delete_requires_delete_permission(): void
    {
        $tenant = $this->tenant(
            'leads-delete-permission'
        );

        $user = $this->user(
            $tenant,
            'delete-permission@leads.local'
        );

        $this->grant(
            $user,
            PermissionEnum::LEADS_VIEW
        );

        app(TenantContext::class)->set($tenant);

        $lead = Lead::create([
            'name' => 'Preservado',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://leads-delete-permission.localhost/leads/{$lead->id}"
            );

        $response->assertForbidden();

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            Lead::query()
                ->whereKey($lead->id)
                ->exists()
        );
    }
}
