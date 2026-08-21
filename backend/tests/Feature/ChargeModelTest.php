<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ChargeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_is_created_in_current_tenant(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-tenant'
        );

        $charge = $this->charge(
            $receivable
        );

        $this->assertSame(
            $tenant->id,
            $charge->tenant_id
        );

        $this->assertSame(
            $receivable->id,
            $charge->receivable_id
        );
    }

    public function test_charge_has_expected_casts(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-casts'
            );

        $charge = $this->charge(
            $receivable,
            [
                'scheduled_at' => now(),
            ]
        );

        $this->assertSame(
            ChargeStatus::PENDING,
            $charge->status
        );

        $this->assertIsInt(
            $charge->attempt
        );

        $this->assertNotNull(
            $charge->scheduled_at
        );
    }

    public function test_charge_defaults_to_pending(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-pending'
            );

        $charge = $this->charge(
            $receivable
        );

        $this->assertSame(
            ChargeStatus::PENDING,
            $charge->status
        );

        $this->assertSame(
            1,
            $charge->attempt
        );
    }

    public function test_charge_relationships_work(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-relations'
        );

        $charge = $this->charge(
            $receivable
        );

        $this->assertTrue(
            $charge->tenant->is(
                $tenant
            )
        );

        $this->assertTrue(
            $charge->receivable->is(
                $receivable
            )
        );
    }

    public function test_charge_queries_are_isolated_by_tenant(): void
    {
        [
            $tenantA,
            $receivableA,
        ] = $this->environment(
            'charge-query-a'
        );

        $chargeA = $this->charge(
            $receivableA
        );

        [, $receivableB] =
            $this->environment(
                'charge-query-b'
            );

        $this->charge(
            $receivableB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertSame(
            [$chargeA->id],
            Charge::query()
                ->pluck('id')
                ->all()
        );
    }

    public function test_charge_from_other_tenant_cannot_be_found(): void
    {
        [$tenantA] =
            $this->environment(
                'charge-find-a'
            );

        [, $receivableB] =
            $this->environment(
                'charge-find-b'
            );

        $foreign = $this->charge(
            $receivableB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Charge::query()->find(
                $foreign->id
            )
        );
    }

    public function test_multiple_charges_can_reference_same_receivable(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-history'
            );

        $first = $this->charge(
            $receivable,
            [
                'attempt' => 1,
            ]
        );

        $second = $this->charge(
            $receivable,
            [
                'attempt' => 2,
            ]
        );

        $this->assertSame(
            $receivable->id,
            $first->receivable_id
        );

        $this->assertSame(
            $receivable->id,
            $second->receivable_id
        );

        $this->assertSame(
            2,
            $receivable
                ->charges()
                ->count()
        );
    }

    public function test_attempt_must_be_positive(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-attempt'
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->charge(
            $receivable,
            [
                'attempt' => 0,
            ]
        );
    }

    public function test_text_fields_are_normalized(): void
    {
        [, $receivable] =
            $this->environment(
                'charge-normalize'
            );

        $charge = $this->charge(
            $receivable,
            [
                'channel' => '  email  ',
                'recipient' =>
                    '  billing@example.com  ',
                'external_reference' =>
                    '  EXT-123  ',
                'failure_reason' =>
                    '  Temporary failure  ',
            ]
        );

        $this->assertSame(
            'email',
            $charge->channel
        );

        $this->assertSame(
            'billing@example.com',
            $charge->recipient
        );

        $this->assertSame(
            'EXT-123',
            $charge->external_reference
        );

        $this->assertSame(
            'Temporary failure',
            $charge->failure_reason
        );
    }

    public function test_parent_models_have_charge_relations(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-parents'
        );

        $charge = $this->charge(
            $receivable
        );

        $this->assertTrue(
            $tenant->charges
                ->contains(
                    $charge
                )
        );

        $this->assertTrue(
            $receivable->charges
                ->contains(
                    $charge
                )
        );
    }

    private function charge(
        Receivable $receivable,
        array $override = []
    ): Charge {
        return Charge::query()->create(
            array_merge(
                [
                    'receivable_id' =>
                        $receivable->id,
                    'status' =>
                        ChargeStatus::PENDING,
                    'attempt' => 1,
                    'scheduled_at' =>
                        now()->addHour(),
                    'channel' => 'email',
                    'recipient' =>
                        'billing@example.com',
                ],
                $override
            )
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

        $customer = Customer::query()->create([
            'tenant_id' =>
                $tenant->id,
            'type' => 'company',
            'name' =>
                'Cliente ' . $slug,
        ]);

        $receivable = app(
            ReceivableService::class
        )->create([
            'customer_id' =>
                $customer->id,
            'title' =>
                'Titulo ' . $slug,
            'amount_minor' =>
                100000,
            'due_date' =>
                '2026-10-31',
        ]);

        return [
            $tenant,
            $receivable,
        ];
    }
}