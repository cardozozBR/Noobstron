<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AssignResponsibleActionHandler;
use App\Services\AutomationActionExecutor;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AssignResponsibleActionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_responsible_updates_opportunity(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-main'
        );

        $responsible = $this->user(
            'owner@assign.local'
        );

        $opportunity =
            $this->opportunity();

        $result = app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $opportunity->id,

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            $opportunity->id,
            $result->data[
                'opportunity_id'
            ]
        );

        $this->assertSame(
            $responsible->id,
            $result->data[
                'responsible_user_id'
            ]
        );

        $opportunity->refresh();

        $this->assertSame(
            $responsible->id,
            $opportunity->responsible_user_id
        );
    }

    public function test_executor_can_assign_responsible(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-executor'
        );

        $responsible = $this->user(
            'executor-owner@assign.local'
        );

        $opportunity =
            $this->opportunity();

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::ASSIGN_RESPONSIBLE,
            app(
                AssignResponsibleActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $opportunity->id,

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,

                'trigger' => [
                    'name' =>
                        'lead.created',
                ],
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertDatabaseHas(
            'opportunities',
            [
                'id' =>
                    $opportunity->id,

                'tenant_id' =>
                    $tenant->id,

                'responsible_user_id' =>
                    $responsible->id,
            ]
        );
    }

    public function test_opportunity_id_is_required(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-opportunity-required'
        );

        $responsible = $this->user(
            'required-owner@assign.local'
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_responsible_user_id_is_required(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-user-required'
        );

        $opportunity =
            $this->opportunity();

        $this->expectException(
            RuntimeException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $opportunity->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_zero_responsible_user_id_is_rejected(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-zero'
        );

        $opportunity =
            $this->opportunity();

        $this->expectException(
            RuntimeException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $opportunity->id,

                    'responsible_user_id' =>
                        0,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
    }

    public function test_numeric_string_ids_are_supported(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-string'
        );

        $responsible = $this->user(
            'string-owner@assign.local'
        );

        $opportunity =
            $this->opportunity();

        $result = app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        (string) $opportunity->id,

                    'responsible_user_id' =>
                        (string) $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            $responsible->id,
            $result->data[
                'responsible_user_id'
            ]
        );
    }

    public function test_responsible_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'assign-responsible-tenant-a'
        );

        $opportunity =
            $this->opportunity();

        $this->tenant(
            'assign-responsible-tenant-b'
        );

        $foreignResponsible =
            $this->user(
                'foreign-owner@assign.local'
            );

        app(TenantContext::class)
            ->set($tenantA);

        $this->expectException(
            RuntimeException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenantA->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $opportunity->id,

                    'responsible_user_id' =>
                        $foreignResponsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenantA->id,
            ]
        );
    }

    public function test_opportunity_from_other_tenant_is_rejected(): void
    {
        $this->tenant(
            'assign-responsible-opportunity-a'
        );

        $foreignOpportunity =
            $this->opportunity();

        $tenantB = $this->tenant(
            'assign-responsible-opportunity-b'
        );

        $responsible = $this->user(
            'tenant-b-owner@assign.local'
        );

        $this->expectException(
            ModelNotFoundException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenantB->id,
                AutomationActionType::ASSIGN_RESPONSIBLE,
                [
                    'opportunity_id' =>
                        $foreignOpportunity->id,

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenantB->id,
            ]
        );
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        $tenant = $this->tenant(
            'assign-responsible-wrong-type'
        );

        $responsible = $this->user(
            'wrong-type-owner@assign.local'
        );

        $opportunity =
            $this->opportunity();

        $this->expectException(
            RuntimeException::class
        );

        app(
            AssignResponsibleActionHandler::class
        )->handle(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CHANGE_STAGE,
                [
                    'opportunity_id' =>
                        $opportunity->id,

                    'responsible_user_id' =>
                        $responsible->id,
                ]
            ),
            [
                'tenant_id' =>
                    $tenant->id,
            ]
        );
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
        string $email
    ): User {
        return User::query()
            ->create([
                'name' =>
                    'Responsible',

                'email' =>
                    $email,

                'password' =>
                    Hash::make(
                        'password123'
                    ),

                'role' =>
                    'user',
            ]);
    }

    private function opportunity(): Opportunity
    {
        $customer =
            Customer::query()
                ->create([
                    'type' =>
                        'individual',

                    'name' =>
                        'Customer',
                ]);

        $pipeline =
            Pipeline::query()
                ->create([
                    'name' =>
                        'Sales',

                    'is_default' =>
                        true,

                    'is_active' =>
                        true,
                ]);

        $stage =
            PipelineStage::query()
                ->create([
                    'pipeline_id' =>
                        $pipeline->id,

                    'name' =>
                        'Stage',

                    'position' =>
                        1,

                    'is_active' =>
                        true,
                ]);

        return Opportunity::query()
            ->create([
                'name' =>
                    'Opportunity',

                'customer_id' =>
                    $customer->id,

                'pipeline_id' =>
                    $pipeline->id,

                'pipeline_stage_id' =>
                    $stage->id,

                'value_minor' =>
                    1000,

                'currency' =>
                    'BRL',

                'probability' =>
                    10,
            ]);
    }
}