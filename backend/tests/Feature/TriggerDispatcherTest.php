<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\TriggerType;
use App\Models\Tenant;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use InvalidArgumentException;
use Tests\TestCase;

class TriggerDispatcherTest extends TestCase
{
    public function test_trigger_catalog_contains_expected_values(): void
    {
        $this->assertSame(
            [
                'lead.created',
                'opportunity.stage_changed',
                'proposal.sent',
                'receivable.overdue',
                'customer.inactive',
                'custom',
            ],
            array_map(
                fn (TriggerType $type): string =>
                    $type->value,
                TriggerType::cases()
            )
        );
    }

    public function test_occurrence_preserves_tenant_and_payload(): void
    {
        $occurrence = new TriggerOccurrence(
            type: TriggerType::LEAD_CREATED,
            tenantId: 10,
            subjectType: 'lead',
            subjectId: 55,
            payload: [
                'source' => 'manual',
            ],
        );

        $this->assertSame(
            'lead.created',
            $occurrence->name()
        );

        $this->assertSame(
            10,
            $occurrence->tenantId
        );

        $this->assertSame(
            'lead',
            $occurrence->subjectType
        );

        $this->assertSame(
            55,
            $occurrence->subjectId
        );

        $this->assertSame(
            [
                'source' => 'manual',
            ],
            $occurrence->payload
        );
    }

    public function test_occurrence_can_be_created_for_tenant(): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', 25);

        $occurrence = TriggerOccurrence::forTenant(
            type: TriggerType::PROPOSAL_SENT,
            tenant: $tenant,
            subjectType: 'proposal',
            subjectId: 80,
        );

        $this->assertSame(
            25,
            $occurrence->tenantId
        );

        $this->assertSame(
            'proposal.sent',
            $occurrence->name()
        );

        $this->assertNotNull(
            $occurrence->occurredAt
        );
    }

    public function test_custom_trigger_requires_name(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new TriggerOccurrence(
            type: TriggerType::CUSTOM,
            tenantId: 1,
        );
    }

    public function test_custom_trigger_uses_custom_name(): void
    {
        $occurrence = new TriggerOccurrence(
            type: TriggerType::CUSTOM,
            tenantId: 1,
            customName: 'integration.completed',
        );

        $this->assertSame(
            'integration.completed',
            $occurrence->name()
        );
    }

    public function test_dispatcher_delivers_matching_occurrence(): void
    {
        $received = [];

        $listener = new class($received)
            implements TriggerListener
        {
            public array $received = [];

            public function __construct(
                array $received
            ) {
                $this->received = $received;
            }

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->received[] =
                    $occurrence;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::LEAD_CREATED->value,
            $listener
        );

        $occurrence = new TriggerOccurrence(
            type: TriggerType::LEAD_CREATED,
            tenantId: 1,
            subjectType: 'lead',
            subjectId: 50,
        );

        $dispatcher->dispatch(
            $occurrence
        );

        $this->assertCount(
            1,
            $listener->received
        );

        $this->assertSame(
            $occurrence,
            $listener->received[0]
        );
    }

    public function test_dispatcher_ignores_non_matching_listener(): void
    {
        $listener = new class
            implements TriggerListener
        {
            public int $calls = 0;

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->calls++;
            }
        };

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            TriggerType::PROPOSAL_SENT->value,
            $listener
        );

        $dispatcher->dispatch(
            new TriggerOccurrence(
                type: TriggerType::LEAD_CREATED,
                tenantId: 1,
            )
        );

        $this->assertSame(
            0,
            $listener->calls
        );
    }

    public function test_invalid_tenant_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new TriggerOccurrence(
            type: TriggerType::LEAD_CREATED,
            tenantId: 0,
        );
    }
}
