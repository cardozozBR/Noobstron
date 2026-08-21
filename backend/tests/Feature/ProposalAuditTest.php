<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProposalService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProposalAuditTest extends TestCase
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
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $name
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $tenant->slug
                . '-'
                . str($name)->slug()
                . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function enable(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)
            ->set(
                $tenant,
                Feature::PROPOSALS,
                true
            );
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

    private function proposal(
        string $number = 'PROP-AUDIT'
    ): Proposal {
        return app(
            ProposalService::class
        )->create([
            'number' => $number,
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'Serviço auditado',
                    'quantity' => 1,
                    'unit_price_minor' => 1000,
                ],
            ],
        ]);
    }

    public function test_proposal_creation_is_audited(): void
    {
        $tenant = $this->tenant(
            'proposal-audit-create'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'create-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals",
                [
                    'number' => 'PROP-AUDIT-CREATE',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Serviço',
                            'quantity' => 1,
                            'unit_price_minor' => 1000,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'proposal.created',
            ]
        );
    }

    public function test_proposal_update_is_audited(): void
    {
        $tenant = $this->tenant(
            'proposal-audit-update'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'update-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_UPDATE
        );

        $proposal = $this->proposal(
            'PROP-AUDIT-BEFORE'
        );

        $this
            ->actingAs($user)
            ->put(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}",
                [
                    'number' => 'PROP-AUDIT-AFTER',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Serviço atualizado',
                            'quantity' => 2,
                            'unit_price_minor' => 2000,
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'proposal.updated',
            ]
        );
    }

    public function test_proposal_delete_is_audited(): void
    {
        $tenant = $this->tenant(
            'proposal-audit-delete'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant,
            'delete-user'
        );

        $this->grant(
            $user,
            PermissionEnum::PROPOSALS_DELETE
        );

        $proposal = $this->proposal(
            'PROP-AUDIT-DELETE'
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}"
            )
            ->assertRedirect(
                route('proposals.index')
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'proposal.deleted',
            ]
        );

        $this->assertDatabaseMissing(
            'proposals',
            [
                'id' => $proposal->id,
            ]
        );
    }

    public function test_proposal_audit_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'proposal-audit-a'
        );

        $this->enable($tenantA);

        $userA = $this->user(
            $tenantA,
            'tenant-a-user'
        );

        $this->grant(
            $userA,
            PermissionEnum::PROPOSALS_CREATE
        );

        $this
            ->actingAs($userA)
            ->post(
                "http://{$tenantA->slug}.localhost/proposals",
                [
                    'number' => 'PROP-TENANT-A',
                    'status' => 'draft',
                    'currency' => 'BRL',
                    'items' => [
                        [
                            'item_type' => 'service',
                            'name' => 'Serviço A',
                            'quantity' => 1,
                            'unit_price_minor' => 1000,
                        ],
                    ],
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'proposal.created'
                )
                ->count()
        );

        $tenantB = $this->tenant(
            'proposal-audit-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            0,
            AuditLog::query()
                ->where(
                    'action',
                    'proposal.created'
                )
                ->count()
        );
    }
}
