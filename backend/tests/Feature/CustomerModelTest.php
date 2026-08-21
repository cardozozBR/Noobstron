<?php

namespace Tests\Feature;

use App\Enums\CustomerContactType;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerEmail;
use App\Models\CustomerHistory;
use App\Models\CustomerPhone;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(
        string $slug
    ): Tenant {
        return Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    public function test_customer_is_created_in_current_tenant(): void
    {
        $tenant = $this->tenant(
            'customer-current'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Maria Silva',
        ]);

        $this->assertSame(
            $tenant->id,
            $customer->tenant_id
        );

        $this->assertDatabaseHas(
            'customers',
            [
                'id' => $customer->id,
                'tenant_id' => $tenant->id,
                'type' => 'individual',
                'name' => 'Maria Silva',
            ]
        );
    }

    public function test_customer_type_is_cast_to_enum(): void
    {
        $tenant = $this->tenant(
            'customer-type'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Acme',
        ]);

        $this->assertSame(
            CustomerType::COMPANY,
            $customer->fresh()->type
        );
    }

    public function test_customer_tags_are_cast_to_array(): void
    {
        $tenant = $this->tenant(
            'customer-tags'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Acme',
            'tags' => [
                'vip',
                'enterprise',
            ],
        ]);

        $this->assertSame(
            [
                'vip',
                'enterprise',
            ],
            $customer->fresh()->tags
        );
    }

    public function test_customer_queries_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-a'
        );

        $tenantB = $this->tenant(
            'customer-b'
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $customerA = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente A',
        ]);

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Cliente B',
        ]);

        $ids = Customer::query()
            ->pluck('id')
            ->all();

        $this->assertContains(
            $customerB->id,
            $ids
        );

        $this->assertNotContains(
            $customerA->id,
            $ids
        );
    }

    public function test_customer_from_another_tenant_cannot_be_found(): void
    {
        $tenantA = $this->tenant(
            'customer-find-a'
        );

        $tenantB = $this->tenant(
            'customer-find-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Outro tenant',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->assertNull(
            Customer::find($customer->id)
        );
    }

    public function test_customer_can_have_contact(): void
    {
        $tenant = $this->tenant(
            'customer-contact'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa',
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => 'João',
            'type' => CustomerContactType::COMMERCIAL,
        ]);

        $this->assertTrue(
            $customer->contacts()
                ->whereKey($contact->id)
                ->exists()
        );

        $this->assertSame(
            CustomerContactType::COMMERCIAL,
            $contact->fresh()->type
        );
    }

    public function test_customer_can_have_phone(): void
    {
        $tenant = $this->tenant(
            'customer-phone'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente',
        ]);

        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'country_code' => 'BR',
            'national_number' => '85999999999',
            'is_primary' => true,
        ]);

        $this->assertTrue(
            $customer->phones()
                ->whereKey($phone->id)
                ->exists()
        );

        $this->assertTrue(
            $phone->fresh()->is_primary
        );
    }

    public function test_customer_can_have_email(): void
    {
        $tenant = $this->tenant(
            'customer-email'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente',
        ]);

        $email = CustomerEmail::create([
            'customer_id' => $customer->id,
            'email' => 'cliente@example.local',
            'is_primary' => true,
        ]);

        $this->assertTrue(
            $customer->emails()
                ->whereKey($email->id)
                ->exists()
        );

        $this->assertTrue(
            $email->fresh()->is_primary
        );
    }

    public function test_customer_can_have_address(): void
    {
        $tenant = $this->tenant(
            'customer-address'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa',
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'country_code' => 'BR',
            'line1' => 'Rua Principal, 100',
            'city' => 'Fortaleza',
            'region' => 'CE',
            'postal_code' => '60000000',
            'is_primary' => true,
        ]);

        $this->assertTrue(
            $customer->addresses()
                ->whereKey($address->id)
                ->exists()
        );
    }

    public function test_customer_can_have_history(): void
    {
        $tenant = $this->tenant(
            'customer-history'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa',
        ]);

        $history = CustomerHistory::create([
            'customer_id' => $customer->id,
            'event' => 'customer.created',
            'description' => 'Cliente criado.',
        ]);

        $this->assertTrue(
            $customer->history()
                ->whereKey($history->id)
                ->exists()
        );
    }

    public function test_tenant_has_customers_relation(): void
    {
        $tenant = $this->tenant(
            'customer-tenant-relation'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente',
        ]);

        $this->assertTrue(
            $tenant->customers()
                ->whereKey($customer->id)
                ->exists()
        );
    }

    public function test_contact_cannot_reference_customer_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-contact-a'
        );

        $tenantB = $this->tenant(
            'customer-contact-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerContact::create([
            'customer_id' => $customerB->id,
            'name' => 'Contato inválido',
            'type' => CustomerContactType::GENERAL,
        ]);
    }

    public function test_phone_cannot_reference_customer_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-phone-a'
        );

        $tenantB = $this->tenant(
            'customer-phone-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerPhone::create([
            'customer_id' => $customerB->id,
            'country_code' => 'BR',
            'national_number' => '85999999999',
        ]);
    }

    public function test_email_cannot_reference_customer_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-email-a'
        );

        $tenantB = $this->tenant(
            'customer-email-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerEmail::create([
            'customer_id' => $customerB->id,
            'email' => 'invalid@example.local',
        ]);
    }

    public function test_address_cannot_reference_customer_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-address-a'
        );

        $tenantB = $this->tenant(
            'customer-address-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerAddress::create([
            'customer_id' => $customerB->id,
            'country_code' => 'BR',
            'line1' => 'Rua Inválida',
            'city' => 'Fortaleza',
        ]);
    }

    public function test_history_cannot_reference_customer_from_another_tenant(): void
    {
        $tenantA = $this->tenant(
            'customer-history-a'
        );

        $tenantB = $this->tenant(
            'customer-history-b'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa B',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerHistory::create([
            'customer_id' => $customerB->id,
            'event' => 'invalid.cross_tenant',
        ]);
    }

    public function test_phone_contact_must_belong_to_same_customer(): void
    {
        $tenant = $this->tenant(
            'customer-phone-contact'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerA = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa A',
        ]);

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa B',
        ]);

        $contactB = CustomerContact::create([
            'customer_id' => $customerB->id,
            'name' => 'Contato B',
            'type' => CustomerContactType::GENERAL,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerPhone::create([
            'customer_id' => $customerA->id,
            'customer_contact_id' => $contactB->id,
            'country_code' => 'BR',
            'national_number' => '85999999999',
        ]);
    }

    public function test_email_contact_must_belong_to_same_customer(): void
    {
        $tenant = $this->tenant(
            'customer-email-contact'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerA = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa A',
        ]);

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa B',
        ]);

        $contactB = CustomerContact::create([
            'customer_id' => $customerB->id,
            'name' => 'Contato B',
            'type' => CustomerContactType::GENERAL,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        CustomerEmail::create([
            'customer_id' => $customerA->id,
            'customer_contact_id' => $contactB->id,
            'email' => 'invalid@example.local',
        ]);
    }
}
