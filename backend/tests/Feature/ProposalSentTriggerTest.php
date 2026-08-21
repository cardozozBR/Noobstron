<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Enums\ProposalStatus;
use App\Enums\TriggerType;
use App\Models\Permission;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProposalService;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TenantCapabilities;
use App\Support\TriggerOccurrence;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProposalSentTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_successful_send_dispatches_proposal_sent_trigger(): void
    {
        Mail::fake();

        $tenant = $this->tenant(
            'proposal-trigger-send'
        );

        $this->enable($tenant);

        $user = $this->user(
            $tenant
        );

        $this->grantUpdate(
            $user
        );

        $proposal = $this->proposal();

        $listener = new class implements TriggerListener {
            public array $occurrences = [];

            public function supports(
                TriggerOccurrence $occurrence
            ): bool {
                return $occurrence->type ===
                    TriggerType::PROPOSAL_SENT;
            }

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] =
                    $occurrence;
            }
        };

     $dispatcher = new TriggerDispatcher();

$dispatcher->listen(
    TriggerType::PROPOSAL_SENT->value,
    $listener
);

$this->app->instance(
    TriggerDispatcher::class,
    $dispatcher
);

        $this
            ->actingAs($user)
            ->post(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/send",
                [
                    'email' =>
                        'customer@example.com',
                ]
            )
            ->assertRedirect(
                route(
                    'proposals.edit',
                    $proposal->id
                )
            );

        $proposal->refresh();

        $this->assertSame(
            ProposalStatus::SENT,
            $proposal->status
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $occurrence =
            $listener->occurrences[0];

        $this->assertSame(
            TriggerType::PROPOSAL_SENT,
            $occurrence->type
        );

        $this->assertSame(
            (int) $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            'proposal',
            $occurrence->subjectType
        );

        $this->assertSame(
            $proposal->id,
            $occurrence->subjectId
        );

        $this->assertSame(
            (int) $proposal->id,
            $occurrence->payload[
                'proposal_id'
            ]
        );

        $this->assertSame(
            'customer@example.com',
            $occurrence->payload[
                'email'
            ]
        );

        $this->assertSame(
            ProposalStatus::SENT->value,
            $occurrence->payload[
                'status'
            ]
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
            'timezone' =>
                'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Proposal Trigger User',
            'email' =>
                $tenant->slug
                . '-trigger@local',
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
                PermissionEnum::PROPOSALS_UPDATE
                    ->value
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

    private function proposal(): Proposal
    {
        return app(
            ProposalService::class
        )->create([
            'number' =>
                'PROP-TRIGGER-SEND',
            'status' => 'draft',
            'items' => [
                [
                    'item_type' =>
                        'service',
                    'name' =>
                        'Servico trigger',
                    'quantity' => 1,
                    'unit_price_minor' =>
                        5000,
                ],
            ],
        ]);
    }
}