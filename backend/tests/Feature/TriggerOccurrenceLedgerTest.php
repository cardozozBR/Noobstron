<?php

namespace Tests\Feature;

use App\Enums\TriggerType;
use App\Models\Tenant;
use App\Models\TriggerOccurrenceRecord;
use App\Services\TenantContext;
use App\Services\TriggerOccurrenceLedger;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriggerOccurrenceLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_occurrence_can_be_claimed_once(): void
    {
        $tenant = $this->tenant(
            'trigger-ledger-once'
        );

        $occurrence =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::RECEIVABLE_OVERDUE,

                tenant:
                    $tenant,

                subjectType:
                    'App\\Models\\Receivable',

                subjectId:
                    123
            );

        $ledger = app(
            TriggerOccurrenceLedger::class
        );

        $this->assertTrue(
            $ledger->claim(
                $occurrence,
                '2026-08-17'
            )
        );

        $this->assertFalse(
            $ledger->claim(
                $occurrence,
                '2026-08-17'
            )
        );

        $this->assertSame(
            1,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_different_boundary_can_be_claimed(): void
    {
        $tenant = $this->tenant(
            'trigger-ledger-boundary'
        );

        $occurrence =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::CUSTOM,

                tenant:
                    $tenant,

                subjectType:
                    'App\\Models\\Customer',

                subjectId:
                    456,

                customName:
                    'customer.followup'
            );

        $ledger = app(
            TriggerOccurrenceLedger::class
        );

        $this->assertTrue(
            $ledger->claim(
                $occurrence,
                '2026-08-17'
            )
        );

        $this->assertTrue(
            $ledger->claim(
                $occurrence,
                '2026-08-18'
            )
        );

        $this->assertSame(
            2,
            TriggerOccurrenceRecord::query()
                ->count()
        );
    }

    public function test_same_identity_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'trigger-ledger-a'
        );

        $occurrenceA =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::RECEIVABLE_OVERDUE,

                tenant:
                    $tenantA,

                subjectType:
                    'App\\Models\\Receivable',

                subjectId:
                    789
            );

        $ledger = app(
            TriggerOccurrenceLedger::class
        );

        $this->assertTrue(
            $ledger->claim(
                $occurrenceA,
                '2026-08-17'
            )
        );

        $tenantB = $this->tenant(
            'trigger-ledger-b'
        );

        $occurrenceB =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::RECEIVABLE_OVERDUE,

                tenant:
                    $tenantB,

                subjectType:
                    'App\\Models\\Receivable',

                subjectId:
                    789
            );

        $this->assertTrue(
            $ledger->claim(
                $occurrenceB,
                '2026-08-17'
            )
        );
    }

    public function test_subject_is_required(): void
    {
        $tenant = $this->tenant(
            'trigger-ledger-subject'
        );

        $occurrence =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::RECEIVABLE_OVERDUE,

                tenant:
                    $tenant
            );

        $this->expectException(
            \InvalidArgumentException::class
        );

        app(
            TriggerOccurrenceLedger::class
        )->claim(
            $occurrence,
            '2026-08-17'
        );
    }

    public function test_boundary_is_required(): void
    {
        $tenant = $this->tenant(
            'trigger-ledger-empty-boundary'
        );

        $occurrence =
            TriggerOccurrence::forTenant(
                type:
                    TriggerType::RECEIVABLE_OVERDUE,

                tenant:
                    $tenant,

                subjectType:
                    'App\\Models\\Receivable',

                subjectId:
                    321
            );

        $this->expectException(
            \InvalidArgumentException::class
        );

        app(
            TriggerOccurrenceLedger::class
        )->claim(
            $occurrence,
            '   '
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
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}