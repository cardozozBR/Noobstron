<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\Feature;
use App\Enums\TriggerType;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_lead_dispatches_lead_created_trigger(): void
    {
        $tenant = $this->tenant(
            'lead-trigger'
        );

        $user = $this->admin(
            $tenant
        );

        $listener = new class
            implements TriggerListener
        {
            /**
             * @var list<TriggerOccurrence>
             */
            public array $occurrences = [];

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] =
                    $occurrence;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::LEAD_CREATED->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );

        $this->actingAs(
            $user
        );

        $response = $this->post(
            "http://{$tenant->slug}.localhost/leads",
            [
                'name' => 'Lead Trigger',
                'email' => 'trigger@example.com',
                'phone' => '',
                'status' => 'new',
                'source' => 'manual',
                'tags' => '',
                'notes' => '',
                'responsible_user_id' => '',
            ]
        );

        $response->assertRedirect();

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $occurrence =
            $listener->occurrences[0];

        $this->assertSame(
            TriggerType::LEAD_CREATED,
            $occurrence->type
        );

        $this->assertSame(
            $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            'lead',
            $occurrence->subjectType
        );

        $this->assertIsInt(
            $occurrence->subjectId
        );

        $this->assertSame(
            $occurrence->subjectId,
            $occurrence->payload['lead_id']
        );

        $this->assertSame(
            'Lead Trigger',
            $occurrence->payload['name']
        );
    }

    public function test_lead_trigger_is_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'lead-trigger-a'
        );

        $tenantB = $this->tenant(
            'lead-trigger-b'
        );

        $userA = $this->admin(
            $tenantA
        );

        $listener = new class
            implements TriggerListener
        {
            /**
             * @var list<TriggerOccurrence>
             */
            public array $occurrences = [];

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] =
                    $occurrence;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::LEAD_CREATED->value,
            $listener
        );

        $this->app->instance(
            TriggerDispatcher::class,
            $dispatcher
        );

        $this->actingAs(
            $userA
        );

        $this->post(
            "http://{$tenantA->slug}.localhost/leads",
            [
                'name' => 'Tenant A Lead',
                'email' => 'tenant-a@example.com',
                'phone' => '',
                'status' => 'new',
                'source' => 'manual',
                'tags' => '',
                'notes' => '',
                'responsible_user_id' => '',
            ]
        )->assertRedirect();

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $this->assertSame(
            $tenantA->id,
            $listener->occurrences[0]->tenantId
        );

        $this->assertNotSame(
            $tenantB->id,
            $listener->occurrences[0]->tenantId
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Sao_Paulo',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        TenantFeature::query()->create([
            'tenant_id' => $tenant->id,
            'feature' => Feature::LEADS->value,
            'enabled' => true,
        ]);

        return $tenant;
    }

    private function admin(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::query()->create([
            'name' => 'Admin',
            'email' =>
                'admin+' . $tenant->slug . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $permission = Permission::query()
            ->firstOrCreate(
                [
                    'name' => 'leads.create',
                ],
                [
                    'label' => 'Criar leads',
                ]
            );

        $user->permissions()->syncWithoutDetaching([
            $permission->id,
        ]);

        return $user;
    }
}