<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SaleAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_close_is_audited(): void
    {
        [$tenant, $opportunity, $user] =
            $this->environment('sale-audit');

        $this->actingAs($user);

        $sale = app(SaleService::class)
            ->close(
                $opportunity,
                [
                    'number' => 'SALE-AUDIT-001',
                ]
            );

        $log = AuditLog::query()
            ->where(
                'action',
                'sale.closed'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $log->tenant_id
        );

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertStringContainsString(
            $sale->number,
            (string) $log->description
        );

        $this->assertStringContainsString(
            $opportunity->name,
            (string) $log->description
        );
    }

    public function test_failed_close_does_not_create_extra_audit(): void
    {
        [, $opportunity, $user] =
            $this->environment(
                'sale-audit-failed'
            );

        $this->actingAs($user);

        app(SaleService::class)
            ->close($opportunity);

        try {
            app(SaleService::class)
                ->close($opportunity);

            $this->fail(
                'Expected duplicate close failure.'
            );
        } catch (RuntimeException) {
            //
        }

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'sale.closed'
                )
                ->count()
        );
    }

    public function test_sale_audit_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $opportunityA,
            $userA,
        ] = $this->environment(
            'sale-audit-a'
        );

        $this->actingAs($userA);

        app(SaleService::class)
            ->close($opportunityA);

        [
            $tenantB,
            $opportunityB,
            $userB,
        ] = $this->environment(
            'sale-audit-b'
        );

        $this->actingAs($userB);

        app(SaleService::class)
            ->close($opportunityB);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'sale.closed'
                )
                ->count()
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertSame(
            1,
            AuditLog::query()
                ->where(
                    'action',
                    'sale.closed'
                )
                ->count()
        );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
            'name' =>
                'Tenant ' . $slug,
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

        $user = User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' => 'Sales Auditor',
            'email' =>
                $slug . '@local',
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' =>
                $tenant->id,
            'type' => 'company',
            'name' =>
                'Cliente ' . $slug,
        ]);

        $pipeline = Pipeline::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' =>
                'Pipeline ' . $slug,
            'is_default' => true,
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'tenant_id' =>
                $tenant->id,
            'pipeline_id' =>
                $pipeline->id,
            'name' => 'Fechamento',
            'position' => 1,
            'is_active' => true,
        ]);

        $opportunity = Opportunity::query()->create([
            'tenant_id' =>
                $tenant->id,
            'customer_id' =>
                $customer->id,
            'pipeline_id' =>
                $pipeline->id,
            'pipeline_stage_id' =>
                $stage->id,
            'name' =>
                'Oportunidade ' . $slug,
            'currency' => 'BRL',
            'value_minor' => 100000,
            'probability' => 100,
        ]);

        return [
            $tenant,
            $opportunity,
            $user,
        ];
    }
}
