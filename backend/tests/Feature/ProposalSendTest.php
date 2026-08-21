<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Enums\ProposalStatus;
use App\Mail\ProposalMail;
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
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ProposalSendTest extends TestCase
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
        string $name = 'Send User'
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $tenant->slug
                . '-send@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grantUpdate(
        User $user
    ): void {
        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::PROPOSALS_UPDATE->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $permission->id
            );
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

    private function proposal(
        string $number = 'PROP-SEND'
    ): Proposal {
        return app(
            ProposalService::class
        )->create([
            'number' => $number,
            'status' => 'draft',
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'Serviço enviado',
                    'quantity' => 2,
                    'unit_price_minor' => 5000,
                ],
            ],
        ]);
    }

    public function test_send_requires_authentication(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'proposal-send-auth'
        );

        $this->enable($tenant);

        $proposal = $this->proposal();

        $this->post(
            "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
            [
                'email' => 'client@example.com',
            ]
        )->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_send_requires_update_permission(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'proposal-send-permission'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant
        );

        $proposal = $this->proposal();

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
                [
                    'email' => 'client@example.com',
                ]
            )
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_send_requires_valid_email(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'proposal-send-validation'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant
        );

        $this->grantUpdate(
            $user
        );

        $proposal = $this->proposal();

        $this
            ->actingAs($user)
            ->from(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/edit"
            )
            ->post(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
                [
                    'email' => 'invalid-email',
                ]
            )
            ->assertSessionHasErrors(
                'email'
            );

        Mail::assertNothingSent();

        $this->assertSame(
            ProposalStatus::DRAFT,
            $proposal->fresh()->status
        );
    }

    public function test_proposal_is_sent_with_pdf_attachment(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'proposal-send-success'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant
        );

        $this->grantUpdate(
            $user
        );

        $proposal = $this->proposal(
            'PROP-SEND-001'
        );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
                [
                    'email' => 'client@example.com',
                ]
            )
            ->assertRedirect(
                route(
                    'proposals.edit',
                    $proposal->id
                )
            );

        Mail::assertSent(
            ProposalMail::class,
            function (
                ProposalMail $mail
            ): bool {
                if (! $mail->hasTo(
                    'client@example.com'
                )) {
                    return false;
                }

                $attachments =
                    $mail->attachments();

                return count($attachments) === 1;
            }
        );

        $this->assertSame(
            ProposalStatus::SENT,
            $proposal->fresh()->status
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'proposal.sent',
            ]
        );
    }

    public function test_other_tenant_proposal_cannot_be_sent(): void
    {
        Mail::fake();

        $tenantA = $this->tenant(
            'proposal-send-a'
        );

        $this->enable($tenantA);

        $userA = $this->user(
            $tenantA
        );

        $this->grantUpdate(
            $userA
        );

        $tenantB = $this->tenant(
            'proposal-send-b'
        );

        $this->enable($tenantB);

        $foreign = $this->proposal(
            'PROP-SEND-FOREIGN'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->post(
                "http://{$tenantA->slug}.localhost/proposals/{$foreign->id}/send",
                [
                    'email' => 'client@example.com',
                ]
            )
            ->assertNotFound();

        Mail::assertNothingSent();

        $this->assertSame(
            ProposalStatus::DRAFT,
            $foreign->fresh()->status
        );
    }

    public function test_failed_delivery_does_not_mark_proposal_as_sent(): void
    {
        $tenant = $this->tenant(
            'proposal-send-failure'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant
        );

        $this->grantUpdate(
            $user
        );

        $proposal = $this->proposal(
            'PROP-SEND-FAILURE'
        );

        Mail::shouldReceive('to')
            ->once()
            ->with('client@example.com')
            ->andThrow(
                new RuntimeException(
                    'Simulated mail transport failure.'
                )
            );

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
                [
                    'email' => 'client@example.com',
                ]
            )
            ->assertServerError();

        $this->assertSame(
            ProposalStatus::DRAFT,
            $proposal->fresh()->status
        );

        $this->assertDatabaseMissing(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'proposal.sent',
            ]
        );
    }
}
