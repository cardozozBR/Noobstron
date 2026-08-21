<?php

namespace Tests\Feature;

use App\Contracts\TriggerListener;
use App\Enums\TriggerType;
use App\Models\Tenant;
use App\Services\CustomTriggerService;
use App\Services\TriggerDispatcher;
use App\Support\TriggerOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CustomTriggerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_event_is_dispatched_by_name(): void
    {
        $tenant = $this->tenant(
            'custom-event'
        );

        $listener = $this->listener();

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            'integration.customer.synced',
            $listener
        );

        $service = new CustomTriggerService(
            $dispatcher
        );

        $occurrence = $service->dispatch(
            tenant: $tenant,
            name: 'integration.customer.synced',
            payload: [
                'source' => 'external',
            ],
        );

        $this->assertSame(
            TriggerType::CUSTOM,
            $occurrence->type
        );

        $this->assertSame(
            'integration.customer.synced',
            $occurrence->name()
        );

        $this->assertSame(
            (int) $tenant->id,
            $occurrence->tenantId
        );

        $this->assertSame(
            [
                'source' => 'external',
            ],
            $occurrence->payload
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );

        $this->assertSame(
            $occurrence,
            $listener->occurrences[0]
        );
    }

    public function test_custom_event_name_is_trimmed(): void
    {
        $tenant = $this->tenant(
            'custom-trim'
        );

        $listener = $this->listener();

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            'customer.imported',
            $listener
        );

        $service = new CustomTriggerService(
            $dispatcher
        );

        $occurrence = $service->dispatch(
            tenant: $tenant,
            name: '  customer.imported  ',
        );

        $this->assertSame(
            'customer.imported',
            $occurrence->name()
        );

        $this->assertCount(
            1,
            $listener->occurrences
        );
    }

    public function test_custom_event_requires_name(): void
    {
        $tenant = $this->tenant(
            'custom-name-required'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $service = new CustomTriggerService(
            new TriggerDispatcher()
        );

        $service->dispatch(
            tenant: $tenant,
            name: '   ',
        );
    }

    public function test_custom_event_preserves_subject(): void
    {
        $tenant = $this->tenant(
            'custom-subject'
        );

        $service = new CustomTriggerService(
            new TriggerDispatcher()
        );

        $occurrence = $service->dispatch(
            tenant: $tenant,
            name: 'customer.enriched',
            subjectType: 'customer',
            subjectId: 123,
        );

        $this->assertSame(
            'customer',
            $occurrence->subjectType
        );

        $this->assertSame(
            123,
            $occurrence->subjectId
        );
    }

    public function test_partial_subject_is_rejected(): void
    {
        $tenant = $this->tenant(
            'custom-partial-subject'
        );

        $service = new CustomTriggerService(
            new TriggerDispatcher()
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->dispatch(
            tenant: $tenant,
            name: 'customer.enriched',
            subjectType: 'customer',
        );
    }

    public function test_custom_event_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'custom-tenant-a'
        );

        $tenantB = $this->tenant(
            'custom-tenant-b'
        );

        $listener = $this->listener();

        $dispatcher = new TriggerDispatcher();

        $dispatcher->listen(
            'customer.synced',
            $listener
        );

        $service = new CustomTriggerService(
            $dispatcher
        );

        $service->dispatch(
            tenant: $tenantA,
            name: 'customer.synced',
        );

        $service->dispatch(
            tenant: $tenantB,
            name: 'customer.synced',
        );

        $this->assertCount(
            2,
            $listener->occurrences
        );

        $this->assertSame(
            (int) $tenantA->id,
            $listener->occurrences[0]->tenantId
        );

        $this->assertSame(
            (int) $tenantB->id,
            $listener->occurrences[1]->tenantId
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    private function listener(): object
    {
        return new class
            implements TriggerListener
        {
            public array $occurrences = [];

            public function handle(
                TriggerOccurrence $occurrence
            ): void {
                $this->occurrences[] =
                    $occurrence;
            }
        };
    }
}