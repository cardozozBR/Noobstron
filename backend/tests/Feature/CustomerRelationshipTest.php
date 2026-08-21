<?php

namespace Tests\Feature;

use App\Enums\CustomerContactType;
use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerEmail;
use App\Models\CustomerPhone;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

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

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            true
        );

        return $tenant;
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::create([
            'name' => 'Customer Manager',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function grant(
        User $user,
        PermissionEnum $permission
    ): void {
        $model = Permission::query()
            ->where(
                'name',
                $permission->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching(
                $model->id
            );
    }

    private function customer(
        Tenant $tenant,
        string $name = 'Cliente'
    ): Customer {
        app(TenantContext::class)->set(
            $tenant
        );

        return Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => $name,
        ]);
    }

    public function test_customer_detail_can_be_viewed(): void
    {
        $tenant = $this->tenant(
            'customer-detail'
        );

        $user = $this->user(
            $tenant,
            'detail@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        $customer = $this->customer(
            $tenant,
            'Empresa Detalhe'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                "http://customer-detail.localhost/customers/{$customer->id}"
            );

        $response->assertOk();
        $response->assertSee(
            'Empresa Detalhe'
        );

        $response->assertSee(
            'Contatos'
        );

        $response->assertSee(
            'Telefones'
        );

        $response->assertSee(
            'E-mails'
        );

        $response->assertSee(
            'Endereços'
        );

        $response->assertSee(
            'Histórico'
        );
    }

    public function test_contact_can_be_created_updated_and_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-contact-crud'
        );

        $user = $this->user(
            $tenant,
            'contact@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-contact-crud.localhost/customers/{$customer->id}/contacts",
                [
                    'name' => 'Maria Comercial',
                    'role' => 'Gerente',
                    'type' => 'commercial',
                    'notes' => 'Contato principal.',
                ]
            );

        $response->assertRedirect(
            route(
                'customers.show',
                $customer->id
            )
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $contact = CustomerContact::query()
            ->firstOrFail();

        $this->assertSame(
            CustomerContactType::COMMERCIAL,
            $contact->type
        );

        $this
            ->actingAs($user)
            ->put(
                "http://customer-contact-crud.localhost/customers/{$customer->id}/contacts/{$contact->id}",
                [
                    'name' => 'Maria Financeiro',
                    'role' => 'Gerente Financeira',
                    'type' => 'financial',
                ]
            )
            ->assertRedirect();

        $contact->refresh();

        $this->assertSame(
            'Maria Financeiro',
            $contact->name
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://customer-contact-crud.localhost/customers/{$customer->id}/contacts/{$contact->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'customer_contacts',
            [
                'id' => $contact->id,
            ]
        );
    }

    public function test_phone_is_normalized_and_can_reference_contact(): void
    {
        $tenant = $this->tenant(
            'customer-phone'
        );

        $user = $this->user(
            $tenant,
            'phone@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => 'Contato',
            'type' => CustomerContactType::GENERAL,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-phone.localhost/customers/{$customer->id}/phones",
                [
                    'customer_contact_id' =>
                        $contact->id,

                    'label' => 'Celular',
                    'country_code' => 'BR',
                    'national_number' =>
                        '(85) 99999-9999',

                    'is_primary' => '1',
                ]
            );

        $response->assertRedirect();

        $this->assertDatabaseHas(
            'customer_phones',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'customer_contact_id' => $contact->id,
                'country_code' => 'BR',
                'national_number' => '85999999999',
                'is_primary' => true,
            ]
        );
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $tenant = $this->tenant(
            'customer-phone-invalid'
        );

        $user = $this->user(
            $tenant,
            'phone-invalid@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-phone-invalid.localhost/customers/{$customer->id}/phones",
                [
                    'country_code' => 'BR',
                    'national_number' => '12',
                ]
            );

        $response->assertSessionHasErrors(
            'national_number'
        );
    }

    public function test_new_primary_phone_replaces_previous_primary(): void
    {
        $tenant = $this->tenant(
            'customer-phone-primary'
        );

        $user = $this->user(
            $tenant,
            'phone-primary@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-phone-primary.localhost/customers/{$customer->id}/phones",
                [
                    'country_code' => 'BR',
                    'national_number' => '85999999991',
                    'is_primary' => '1',
                ]
            );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-phone-primary.localhost/customers/{$customer->id}/phones",
                [
                    'country_code' => 'BR',
                    'national_number' => '85999999992',
                    'is_primary' => '1',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            CustomerPhone::query()
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_contact_from_other_customer_is_rejected_for_phone(): void
    {
        $tenant = $this->tenant(
            'customer-phone-contact-invalid'
        );

        $user = $this->user(
            $tenant,
            'phone-contact-invalid@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customerA = $this->customer(
            $tenant,
            'Cliente A'
        );

        $customerB = $this->customer(
            $tenant,
            'Cliente B'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $contactB = CustomerContact::create([
            'customer_id' => $customerB->id,
            'name' => 'Contato B',
            'type' => CustomerContactType::GENERAL,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-phone-contact-invalid.localhost/customers/{$customerA->id}/phones",
                [
                    'customer_contact_id' =>
                        $contactB->id,

                    'country_code' => 'BR',
                    'national_number' =>
                        '85999999999',
                ]
            );

        $response->assertSessionHasErrors(
            'customer_contact_id'
        );
    }

    public function test_email_is_normalized(): void
    {
        $tenant = $this->tenant(
            'customer-email'
        );

        $user = $this->user(
            $tenant,
            'email@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-email.localhost/customers/{$customer->id}/emails",
                [
                    'email' =>
                        '  CLIENTE@Example.COM  ',

                    'is_primary' => '1',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'customer_emails',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'email' => 'cliente@example.com',
                'is_primary' => true,
            ]
        );
    }

    public function test_invalid_email_is_rejected(): void
    {
        $tenant = $this->tenant(
            'customer-email-invalid'
        );

        $user = $this->user(
            $tenant,
            'email-invalid@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-email-invalid.localhost/customers/{$customer->id}/emails",
                [
                    'email' => 'invalid',
                ]
            );

        $response->assertSessionHasErrors(
            'email'
        );
    }

    public function test_address_is_normalized(): void
    {
        $tenant = $this->tenant(
            'customer-address'
        );

        $user = $this->user(
            $tenant,
            'address@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-address.localhost/customers/{$customer->id}/addresses",
                [
                    'label' => 'Principal',
                    'country_code' => 'BR',
                    'line1' => '  Rua Principal, 100  ',
                    'line2' => '  Sala 10  ',
                    'city' => '  Fortaleza  ',
                    'region' => '  CE  ',
                    'postal_code' => '  60000-000  ',
                    'is_primary' => '1',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'customer_addresses',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'country_code' => 'BR',
                'line1' => 'Rua Principal, 100',
                'line2' => 'Sala 10',
                'city' => 'Fortaleza',
                'region' => 'CE',
                'postal_code' => '60000-000',
                'is_primary' => true,
            ]
        );
    }

    public function test_related_changes_generate_customer_history(): void
    {
        $tenant = $this->tenant(
            'customer-history-related'
        );

        $user = $this->user(
            $tenant,
            'history@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-history-related.localhost/customers/{$customer->id}/contacts",
                [
                    'name' => 'Contato Histórico',
                    'type' => 'general',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'customer_history',
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'event' =>
                    'customer.contact.created',
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' =>
                    'customer.contact.created',
            ]
        );
    }

    public function test_customer_creation_generates_history(): void
    {
        $tenant = $this->tenant(
            'customer-main-history'
        );

        $user = $this->user(
            $tenant,
            'main-history@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://customer-main-history.localhost/customers',
                [
                    'type' => 'company',
                    'name' => 'Empresa Histórica',
                ]
            );

        $response->assertRedirect();

        $this->assertDatabaseHas(
            'customer_history',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'event' => 'customer.created',
            ]
        );
    }

    public function test_other_tenant_customer_cannot_receive_related_data(): void
    {
        $tenantA = $this->tenant(
            'customer-related-a'
        );

        $tenantB = $this->tenant(
            'customer-related-b'
        );

        $userA = $this->user(
            $tenantA,
            'related-a@customers.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customerB = $this->customer(
            $tenantB
        );

        $response = $this
            ->actingAs($userA)
            ->post(
                "http://customer-related-a.localhost/customers/{$customerB->id}/contacts",
                [
                    'name' => 'Inválido',
                    'type' => 'general',
                ]
            );

        $response->assertNotFound();
    }

    public function test_related_data_requires_update_permission(): void
    {
        $tenant = $this->tenant(
            'customer-related-permission'
        );

        $user = $this->user(
            $tenant,
            'related-permission@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        $customer = $this->customer(
            $tenant
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-related-permission.localhost/customers/{$customer->id}/contacts",
                [
                    'name' => 'Bloqueado',
                    'type' => 'general',
                ]
            );

        $response->assertForbidden();
    }

    public function test_related_data_is_blocked_when_feature_is_disabled(): void
    {
        $tenant = $this->tenant(
            'customer-related-feature'
        );

        $user = $this->user(
            $tenant,
            'related-feature@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "http://customer-related-feature.localhost/customers/{$customer->id}/contacts",
                [
                    'name' => 'Bloqueado',
                    'type' => 'general',
                ]
            );

        $response->assertForbidden();
    }

    public function test_phone_can_be_updated_and_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-phone-update-delete'
        );

        $user = $this->user(
            $tenant,
            'phone-update-delete@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'label' => 'Antigo',
            'country_code' => 'BR',
            'national_number' => '85999999991',
            'is_primary' => false,
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://customer-phone-update-delete.localhost/customers/{$customer->id}/phones/{$phone->id}",
                [
                    'label' => 'Novo',
                    'country_code' => 'BR',
                    'national_number' => '(85) 99999-9992',
                    'is_primary' => '1',
                ]
            )
            ->assertRedirect();

        $phone->refresh();

        $this->assertSame(
            'Novo',
            $phone->label
        );

        $this->assertSame(
            '85999999992',
            $phone->national_number
        );

        $this->assertTrue(
            $phone->is_primary
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.phone.updated',
            ]
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://customer-phone-update-delete.localhost/customers/{$customer->id}/phones/{$phone->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'customer_phones',
            [
                'id' => $phone->id,
            ]
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.phone.deleted',
            ]
        );
    }

    public function test_email_can_be_updated_and_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-email-update-delete'
        );

        $user = $this->user(
            $tenant,
            'email-update-delete@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $email = CustomerEmail::create([
            'customer_id' => $customer->id,
            'label' => 'Antigo',
            'email' => 'old@example.com',
            'is_primary' => false,
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://customer-email-update-delete.localhost/customers/{$customer->id}/emails/{$email->id}",
                [
                    'label' => 'Principal',
                    'email' => '  NEW@Example.COM  ',
                    'is_primary' => '1',
                ]
            )
            ->assertRedirect();

        $email->refresh();

        $this->assertSame(
            'Principal',
            $email->label
        );

        $this->assertSame(
            'new@example.com',
            $email->email
        );

        $this->assertTrue(
            $email->is_primary
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.email.updated',
            ]
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://customer-email-update-delete.localhost/customers/{$customer->id}/emails/{$email->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'customer_emails',
            [
                'id' => $email->id,
            ]
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.email.deleted',
            ]
        );
    }

    public function test_address_can_be_updated_and_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-address-update-delete'
        );

        $user = $this->user(
            $tenant,
            'address-update-delete@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Antigo',
            'country_code' => 'BR',
            'line1' => 'Rua Antiga, 1',
            'city' => 'Fortaleza',
            'region' => 'CE',
            'postal_code' => '60000-000',
            'is_primary' => false,
        ]);

        $this
            ->actingAs($user)
            ->put(
                "http://customer-address-update-delete.localhost/customers/{$customer->id}/addresses/{$address->id}",
                [
                    'label' => 'Matriz',
                    'country_code' => 'BR',
                    'line1' => ' Rua Nova, 200 ',
                    'line2' => ' Sala 5 ',
                    'city' => ' Fortaleza ',
                    'region' => ' CE ',
                    'postal_code' => ' 60123-456 ',
                    'is_primary' => '1',
                ]
            )
            ->assertRedirect();

        $address->refresh();

        $this->assertSame(
            'Matriz',
            $address->label
        );

        $this->assertSame(
            'Rua Nova, 200',
            $address->line1
        );

        $this->assertSame(
            'Sala 5',
            $address->line2
        );

        $this->assertTrue(
            $address->is_primary
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.address.updated',
            ]
        );

        $this
            ->actingAs($user)
            ->delete(
                "http://customer-address-update-delete.localhost/customers/{$customer->id}/addresses/{$address->id}"
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'customer_addresses',
            [
                'id' => $address->id,
            ]
        );

        $this->assertDatabaseHas(
            'customer_history',
            [
                'customer_id' => $customer->id,
                'event' => 'customer.address.deleted',
            ]
        );
    }

    public function test_new_primary_email_replaces_previous_primary(): void
    {
        $tenant = $this->tenant(
            'customer-email-primary'
        );

        $user = $this->user(
            $tenant,
            'email-primary@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-email-primary.localhost/customers/{$customer->id}/emails",
                [
                    'email' => 'first@example.com',
                    'is_primary' => '1',
                ]
            );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-email-primary.localhost/customers/{$customer->id}/emails",
                [
                    'email' => 'second@example.com',
                    'is_primary' => '1',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            CustomerEmail::query()
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_new_primary_address_replaces_previous_primary(): void
    {
        $tenant = $this->tenant(
            'customer-address-primary'
        );

        $user = $this->user(
            $tenant,
            'address-primary@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customer = $this->customer(
            $tenant
        );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-address-primary.localhost/customers/{$customer->id}/addresses",
                [
                    'country_code' => 'BR',
                    'line1' => 'Rua A',
                    'city' => 'Fortaleza',
                    'is_primary' => '1',
                ]
            );

        $this
            ->actingAs($user)
            ->post(
                "http://customer-address-primary.localhost/customers/{$customer->id}/addresses",
                [
                    'country_code' => 'BR',
                    'line1' => 'Rua B',
                    'city' => 'Fortaleza',
                    'is_primary' => '1',
                ]
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            CustomerAddress::query()
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_other_customer_phone_cannot_be_updated(): void
    {
        $tenant = $this->tenant(
            'customer-phone-cross'
        );

        $user = $this->user(
            $tenant,
            'phone-cross@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customerA = $this->customer(
            $tenant,
            'Customer A'
        );

        $customerB = $this->customer(
            $tenant,
            'Customer B'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $phoneB = CustomerPhone::create([
            'customer_id' => $customerB->id,
            'country_code' => 'BR',
            'national_number' => '85999999999',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "http://customer-phone-cross.localhost/customers/{$customerA->id}/phones/{$phoneB->id}",
                [
                    'country_code' => 'BR',
                    'national_number' => '85988888888',
                ]
            );

        $response->assertNotFound();
    }

    public function test_other_customer_email_cannot_be_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-email-cross'
        );

        $user = $this->user(
            $tenant,
            'email-cross@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customerA = $this->customer(
            $tenant,
            'Customer A'
        );

        $customerB = $this->customer(
            $tenant,
            'Customer B'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $emailB = CustomerEmail::create([
            'customer_id' => $customerB->id,
            'email' => 'customer-b@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://customer-email-cross.localhost/customers/{$customerA->id}/emails/{$emailB->id}"
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'customer_emails',
            [
                'id' => $emailB->id,
            ]
        );
    }

    public function test_other_customer_address_cannot_be_deleted(): void
    {
        $tenant = $this->tenant(
            'customer-address-cross'
        );

        $user = $this->user(
            $tenant,
            'address-cross@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        $customerA = $this->customer(
            $tenant,
            'Customer A'
        );

        $customerB = $this->customer(
            $tenant,
            'Customer B'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $addressB = CustomerAddress::create([
            'customer_id' => $customerB->id,
            'country_code' => 'BR',
            'line1' => 'Rua B',
            'city' => 'Fortaleza',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://customer-address-cross.localhost/customers/{$customerA->id}/addresses/{$addressB->id}"
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'customer_addresses',
            [
                'id' => $addressB->id,
            ]
        );
    }
}
