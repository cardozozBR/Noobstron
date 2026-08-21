<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);
    }

    private function user(
        Tenant $tenant
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'name' => 'Responsável',
            'email' =>
                'responsavel@'
                . $tenant->slug
                . '.local',
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' => 'user',
        ]);
    }

    private function lead(
        Tenant $tenant,
        array $overrides = []
    ): Lead {
        app(TenantContext::class)->set(
            $tenant
        );

        return Lead::create(
            array_merge(
                [
                    'name' => 'Maria Lead',
                    'email' => 'MARIA@EXAMPLE.COM',
                    'phone' => '(85) 99999-9999',
                    'status' => LeadStatus::QUALIFIED,
                    'source' => LeadSource::WEBSITE,
                    'tags' => [
                        'vip',
                        'site',
                    ],
                    'notes' =>
                        'Observação original do lead.',
                ],
                $overrides
            )
        );
    }

    public function test_lead_can_be_converted_to_customer(): void
    {
        $tenant = $this->tenant(
            'lead-conversion'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertInstanceOf(
            Customer::class,
            $customer
        );

        $this->assertSame(
            $tenant->id,
            $customer->tenant_id
        );

        $this->assertSame(
            'Maria Lead',
            $customer->name
        );

        $this->assertSame(
            CustomerType::INDIVIDUAL,
            $customer->type
        );

        $this->assertSame(
            [
                'vip',
                'site',
            ],
            $customer->tags
        );
    }

    public function test_conversion_preserves_responsible(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-owner'
        );

        $responsible = $this->user(
            $tenant
        );

        $lead = $this->lead(
            $tenant,
            [
                'responsible_user_id' =>
                    $responsible->id,
            ]
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertSame(
            $responsible->id,
            $customer->responsible_user_id
        );
    }

    public function test_conversion_creates_primary_email(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-email'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertDatabaseHas(
            'customer_emails',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'email' => 'maria@example.com',
                'is_primary' => true,
            ]
        );
    }

    public function test_conversion_creates_primary_phone_when_valid(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-phone'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertDatabaseHas(
            'customer_phones',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'country_code' => 'BR',
                'national_number' =>
                    '85999999999',
                'is_primary' => true,
            ]
        );
    }

    public function test_invalid_optional_phone_does_not_block_conversion(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-invalid-phone'
        );

        $lead = $this->lead(
            $tenant,
            [
                'phone' => '12',
            ]
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertDatabaseHas(
            'customers',
            [
                'id' => $customer->id,
                'tenant_id' => $tenant->id,
            ]
        );

        $this->assertDatabaseMissing(
            'customer_phones',
            [
                'customer_id' => $customer->id,
            ]
        );
    }

    public function test_lead_records_converted_customer_and_time(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-link'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $lead->refresh();

        $this->assertSame(
            $customer->id,
            $lead->converted_customer_id
        );

        $this->assertNotNull(
            $lead->converted_at
        );

        $this->assertTrue(
            $lead->isConverted()
        );

        $this->assertSame(
            $customer->id,
            $lead->convertedCustomer->id
        );
    }

    public function test_customer_can_reference_source_lead(): void
    {
        $tenant = $this->tenant(
            'customer-source-lead'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertSame(
            $lead->id,
            $customer->sourceLead->id
        );
    }

    public function test_same_lead_cannot_be_converted_twice(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-once'
        );

        $lead = $this->lead(
            $tenant
        );

        app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->expectException(
            ValidationException::class
        );

        app(
            LeadConversionService::class
        )->convert(
            $lead->fresh()
        );
    }

    public function test_duplicate_conversion_does_not_create_second_customer(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-no-duplicate'
        );

        $lead = $this->lead(
            $tenant
        );

        app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        try {
            app(
                LeadConversionService::class
            )->convert(
                $lead->fresh()
            );
        } catch (ValidationException) {
        }

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            Customer::query()->count()
        );
    }

    public function test_conversion_creates_customer_history(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-history'
        );

        $lead = $this->lead(
            $tenant
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'event' =>
                    'customer.converted_from_lead',
            ]
        );
    }

    public function test_conversion_is_audited(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-audit'
        );

        $actor = $this->user(
            $tenant
        );

        $lead = $this->lead(
            $tenant
        );

        $this->actingAs(
            $actor
        );

        app(
            LeadConversionService::class
        )->convert(
            $lead
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $actor->id,
                'action' => 'lead.converted',
            ]
        );
    }

    public function test_conversion_is_isolated_between_tenants(): void
    {
        $tenantA = $this->tenant(
            'lead-conversion-a'
        );

        $tenantB = $this->tenant(
            'lead-conversion-b'
        );

        $leadB = $this->lead(
            $tenantB
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $foreignLead = Lead::query()
            ->findOrFail(
                $leadB->id
            );

        app(
            LeadConversionService::class
        )->convert(
            $foreignLead
        );
    }

    public function test_company_conversion_is_supported(): void
    {
        $tenant = $this->tenant(
            'lead-conversion-company'
        );

        $lead = $this->lead(
            $tenant,
            [
                'name' =>
                    'Empresa Lead',
            ]
        );

        $customer = app(
            LeadConversionService::class
        )->convert(
            $lead,
            CustomerType::COMPANY
        );

        $this->assertSame(
            CustomerType::COMPANY,
            $customer->type
        );

        $this->assertSame(
            'Empresa Lead',
            $customer->name
        );
    }
}
