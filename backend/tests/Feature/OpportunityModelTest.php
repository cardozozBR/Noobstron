<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PipelineService;
use App\Services\PipelineStageService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class OpportunityModelTest extends TestCase
{
    use RefreshDatabase;

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

    private function customer(
        string $name = 'Cliente'
    ): Customer {
        return Customer::create([
            'type' => 'individual',
            'name' => $name,
        ]);
    }

    private function pipeline(): array
    {
        $pipeline = app(
            PipelineService::class
        )->create([
            'name' => 'Comercial',
        ]);

        $stage = app(
            PipelineStageService::class
        )->create(
            $pipeline,
            [
                'name' => 'Novo',
            ]
        );

        return [
            $pipeline,
            $stage,
        ];
    }

    private function opportunity(
        array $overrides = []
    ): Opportunity {
        $customer = $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        return Opportunity::create(
            array_merge(
                [
                    'name' => 'Nova venda',
                    'customer_id' =>
                        $customer->id,
                    'pipeline_id' =>
                        $pipeline->id,
                    'pipeline_stage_id' =>
                        $stage->id,
                    'value_minor' => 150000,
                    'currency' => 'BRL',
                    'probability' => 50,
                ],
                $overrides
            )
        );
    }

    public function test_opportunity_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'opportunity-create'
        );

        $opportunity =
            $this->opportunity();

        $this->assertSame(
            $tenant->id,
            $opportunity->tenant_id
        );
    }

    public function test_opportunity_has_expected_casts(): void
    {
        $this->tenant(
            'opportunity-casts'
        );

        $opportunity =
            $this->opportunity([
                'expected_close_date' =>
                    '2026-12-31',
            ]);

        $this->assertSame(
            150000,
            $opportunity->value_minor
        );

        $this->assertSame(
            50,
            $opportunity->probability
        );

        $this->assertSame(
            '2026-12-31',
            $opportunity
                ->expected_close_date
                ->format('Y-m-d')
        );
    }

    public function test_opportunity_relationships_work(): void
    {
        $this->tenant(
            'opportunity-relations'
        );

        $responsible = User::create([
            'name' => 'Responsável',
            'email' =>
                'responsible@local',
            'password' =>
                Hash::make('password123'),
            'role' => 'user',
        ]);

        $customer =
            $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        $opportunity =
            Opportunity::create([
                'name' => 'Contrato',
                'customer_id' =>
                    $customer->id,
                'pipeline_id' =>
                    $pipeline->id,
                'pipeline_stage_id' =>
                    $stage->id,
                'responsible_user_id' =>
                    $responsible->id,
                'value_minor' => 50000,
                'currency' => 'BRL',
                'probability' => 25,
            ]);

        $this->assertTrue(
            $opportunity->customer->is(
                $customer
            )
        );

        $this->assertTrue(
            $opportunity->pipeline->is(
                $pipeline
            )
        );

        $this->assertTrue(
            $opportunity->stage->is(
                $stage
            )
        );

        $this->assertTrue(
            $opportunity->responsible->is(
                $responsible
            )
        );
    }

    public function test_opportunity_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'opportunity-a'
        );

        $this->opportunity([
            'name' => 'A',
        ]);

        $tenantB = $this->tenant(
            'opportunity-b'
        );

        $this->opportunity([
            'name' => 'B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            ['A'],
            Opportunity::query()
                ->pluck('name')
                ->all()
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            ['B'],
            Opportunity::query()
                ->pluck('name')
                ->all()
        );
    }

    public function test_opportunity_from_other_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant(
            'opportunity-find-a'
        );

        $this->tenant(
            'opportunity-find-b'
        );

        $foreign =
            $this->opportunity();

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Opportunity::query()
                ->find($foreign->id)
        );
    }

    public function test_stage_must_belong_to_selected_pipeline(): void
    {
        $this->tenant(
            'opportunity-stage'
        );

        $customer =
            $this->customer();

        $pipelineService = app(
            PipelineService::class
        );

        $stageService = app(
            PipelineStageService::class
        );

        $pipelineA =
            $pipelineService->create([
                'name' => 'A',
            ]);

        $pipelineB =
            $pipelineService->create([
                'name' => 'B',
            ]);

        $stageB =
            $stageService->create(
                $pipelineB,
                [
                    'name' => 'B1',
                ]
            );

        $this->expectException(
            RuntimeException::class
        );

        Opportunity::create([
            'name' => 'Inválida',
            'customer_id' =>
                $customer->id,
            'pipeline_id' =>
                $pipelineA->id,
            'pipeline_stage_id' =>
                $stageB->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_customer_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->tenant(
            'opportunity-customer-a'
        );

        $customerA =
            $this->customer();

        $this->tenant(
            'opportunity-customer-b'
        );

        [$pipeline, $stage] =
            $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        Opportunity::create([
            'name' => 'Inválida',
            'customer_id' =>
                $customerA->id,
            'pipeline_id' =>
                $pipeline->id,
            'pipeline_stage_id' =>
                $stage->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_responsible_from_other_tenant_is_rejected(): void
    {
        $this->tenant(
            'opportunity-user-a'
        );

        $responsible = User::create([
            'name' => 'User A',
            'email' => 'user-a@local',
            'password' =>
                Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->tenant(
            'opportunity-user-b'
        );

        $customer =
            $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        $this->expectException(
            RuntimeException::class
        );

        Opportunity::create([
            'name' => 'Inválida',
            'customer_id' =>
                $customer->id,
            'pipeline_id' =>
                $pipeline->id,
            'pipeline_stage_id' =>
                $stage->id,
            'responsible_user_id' =>
                $responsible->id,
            'value_minor' => 0,
            'currency' => 'BRL',
            'probability' => 0,
        ]);
    }

    public function test_probability_must_be_between_zero_and_one_hundred(): void
    {
        $this->tenant(
            'opportunity-probability'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->opportunity([
            'probability' => 101,
        ]);
    }

    public function test_negative_value_is_rejected(): void
    {
        $this->tenant(
            'opportunity-value'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->opportunity([
            'value_minor' => -1,
        ]);
    }

    public function test_currency_is_normalized(): void
    {
        $this->tenant(
            'opportunity-currency'
        );

        $opportunity =
            $this->opportunity([
                'currency' => ' brl ',
            ]);

        $this->assertSame(
            'BRL',
            $opportunity->currency
        );
    }

    public function test_parent_models_have_opportunity_relations(): void
    {
        $tenant = $this->tenant(
            'opportunity-parent-relations'
        );

        $responsible = User::create([
            'name' => 'Responsável',
            'email' => 'owner@local',
            'password' =>
                Hash::make('password123'),
            'role' => 'user',
        ]);

        $customer =
            $this->customer();

        [$pipeline, $stage] =
            $this->pipeline();

        Opportunity::create([
            'name' => 'Venda',
            'customer_id' =>
                $customer->id,
            'pipeline_id' =>
                $pipeline->id,
            'pipeline_stage_id' =>
                $stage->id,
            'responsible_user_id' =>
                $responsible->id,
            'value_minor' => 100,
            'currency' => 'BRL',
            'probability' => 10,
        ]);

        $this->assertCount(
            1,
            $tenant->opportunities
        );

        $this->assertCount(
            1,
            $customer->opportunities
        );

        $this->assertCount(
            1,
            $pipeline->opportunities
        );

        $this->assertCount(
            1,
            $stage->opportunities
        );

        $this->assertCount(
            1,
            $responsible
                ->assignedOpportunities
        );
    }
}
