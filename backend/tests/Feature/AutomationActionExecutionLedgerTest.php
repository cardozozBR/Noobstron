<?php

namespace Tests\Feature;

use App\Models\AutomationActionExecution;
use App\Models\Tenant;
use App\Services\AutomationActionExecutionLedger;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationActionExecutionLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_can_be_claimed(): void
    {
        $tenant = $this->tenant(
            'execution-claim'
        );

        $execution = app(
            AutomationActionExecutionLedger::class
        )->claim(
            $tenant->id,
            'execution-1',
            'create_task'
        );

        $this->assertInstanceOf(
            AutomationActionExecution::class,
            $execution
        );

        $this->assertSame(
            $tenant->id,
            $execution->tenant_id
        );

        $this->assertSame(
            'execution-1',
            $execution->execution_key
        );

        $this->assertNull(
            $execution->completed_at
        );
    }

    public function test_duplicate_claim_returns_same_incomplete_execution(): void
    {
        $tenant = $this->tenant(
            'execution-duplicate'
        );

        $ledger = app(
            AutomationActionExecutionLedger::class
        );

        $first = $ledger->claim(
            $tenant->id,
            'same-execution',
            'create_task'
        );

        $second = $ledger->claim(
            $tenant->id,
            'same-execution',
            'create_task'
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertFalse(
            $ledger->isCompleted(
                $second
            )
        );

        $this->assertSame(
            1,
            AutomationActionExecution::query()
                ->where(
                    'execution_key',
                    'same-execution'
                )
                ->count()
        );
    }

    public function test_completed_execution_is_detected(): void
    {
        $tenant = $this->tenant(
            'execution-completed'
        );

        $ledger = app(
            AutomationActionExecutionLedger::class
        );

        $execution = $ledger->claim(
            $tenant->id,
            'completed-key',
            'create_task'
        );

        $this->assertFalse(
            $ledger->isCompleted(
                $execution
            )
        );

        $completed = $ledger->complete(
            $execution
        );

        $this->assertTrue(
            $ledger->isCompleted(
                $completed
            )
        );

        $this->assertNotNull(
            $completed->completed_at
        );
    }

    public function test_complete_is_idempotent(): void
    {
        $tenant = $this->tenant(
            'execution-complete-idempotent'
        );

        $ledger = app(
            AutomationActionExecutionLedger::class
        );

        $execution = $ledger->claim(
            $tenant->id,
            'complete-idempotent',
            'create_task'
        );

        $first = $ledger->complete(
            $execution
        );

        $firstCompletedAt =
            $first->completed_at
                ?->toISOString();

        $second = $ledger->complete(
            $first
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            $firstCompletedAt,
            $second->completed_at
                ?->toISOString()
        );
    }

    public function test_same_key_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'execution-tenant-a'
        );

        $tenantB = $this->tenant(
            'execution-tenant-b'
        );

        $ledger = app(
            AutomationActionExecutionLedger::class
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $first = $ledger->claim(
            $tenantA->id,
            'shared-key',
            'create_task'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $second = $ledger->claim(
            $tenantB->id,
            'shared-key',
            'create_task'
        );

        $this->assertNotSame(
            $first->id,
            $second->id
        );
    }

    public function test_blank_execution_key_is_rejected(): void
    {
        $tenant = $this->tenant(
            'execution-blank-key'
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        app(
            AutomationActionExecutionLedger::class
        )->claim(
            $tenant->id,
            '   ',
            'create_task'
        );
    }

    public function test_blank_action_type_is_rejected(): void
    {
        $tenant = $this->tenant(
            'execution-blank-type'
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        app(
            AutomationActionExecutionLedger::class
        )->claim(
            $tenant->id,
            'execution-key',
            '   '
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
}