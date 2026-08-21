<?php

namespace Tests\Feature;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeadModelTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
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
            'name' => 'Responsável',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    public function test_lead_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'lead-tenant'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Teste',
        ]);

        $this->assertSame(
            $tenant->id,
            $lead->tenant_id
        );

        $this->assertDatabaseHas(
            'leads',
            [
                'id' => $lead->id,
                'tenant_id' => $tenant->id,
                'name' => 'Lead Teste',
            ]
        );
    }

    public function test_lead_uses_default_status_and_source(): void
    {
        $tenant = $this->tenant(
            'lead-defaults'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Default',
        ]);

        $lead->refresh();

        $this->assertSame(
            LeadStatus::NEW,
            $lead->status
        );

        $this->assertSame(
            LeadSource::MANUAL,
            $lead->source
        );
    }

    public function test_status_and_source_are_cast_to_enums(): void
    {
        $tenant = $this->tenant(
            'lead-casts'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Cast',
            'status' => LeadStatus::QUALIFIED,
            'source' => LeadSource::REFERRAL,
        ]);

        $lead->refresh();

        $this->assertSame(
            LeadStatus::QUALIFIED,
            $lead->status
        );

        $this->assertSame(
            LeadSource::REFERRAL,
            $lead->source
        );
    }

    public function test_tags_are_cast_to_array(): void
    {
        $tenant = $this->tenant(
            'lead-tags'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Tags',
            'tags' => [
                'vip',
                'evento',
            ],
        ]);

        $lead->refresh();

        $this->assertSame(
            [
                'vip',
                'evento',
            ],
            $lead->tags
        );
    }

    public function test_lead_can_have_notes(): void
    {
        $tenant = $this->tenant(
            'lead-notes'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Notes',
            'notes' => 'Cliente pediu retorno amanhã.',
        ]);

        $this->assertSame(
            'Cliente pediu retorno amanhã.',
            $lead->notes
        );
    }

    public function test_lead_can_have_responsible_user(): void
    {
        $tenant = $this->tenant(
            'lead-responsible'
        );

        $user = $this->user(
            $tenant,
            'responsavel@lead.local'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Responsável',
            'responsible_user_id' => $user->id,
        ]);

        $this->assertTrue(
            $lead->responsible->is(
                $user
            )
        );
    }

    public function test_tenant_has_leads_relation(): void
    {
        $tenant = $this->tenant(
            'lead-relation'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Relation',
        ]);

        $this->assertTrue(
            $tenant->leads()
                ->whereKey($lead->id)
                ->exists()
        );
    }

    public function test_user_has_assigned_leads_relation(): void
    {
        $tenant = $this->tenant(
            'lead-user-relation'
        );

        $user = $this->user(
            $tenant,
            'assigned@lead.local'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $lead = Lead::create([
            'name' => 'Lead Assigned',
            'responsible_user_id' => $user->id,
        ]);

        $this->assertTrue(
            $user->assignedLeads()
                ->whereKey($lead->id)
                ->exists()
        );
    }

    public function test_lead_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'lead-a'
        );

        $tenantB = $this->tenant(
            'lead-b'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $leadA = Lead::create([
            'name' => 'Lead A',
        ]);

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB = Lead::create([
            'name' => 'Lead B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $ids = Lead::query()
            ->pluck('id')
            ->all();

        $this->assertContains(
            $leadA->id,
            $ids
        );

        $this->assertNotContains(
            $leadB->id,
            $ids
        );
    }

    public function test_lead_from_another_tenant_cannot_be_found_by_id(): void
    {
        $tenantA = $this->tenant(
            'lead-find-a'
        );

        $tenantB = $this->tenant(
            'lead-find-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $leadB = Lead::create([
            'name' => 'Lead B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Lead::find($leadB->id)
        );
    }
}
