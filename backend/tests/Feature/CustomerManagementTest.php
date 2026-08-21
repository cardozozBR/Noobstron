<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\CustomerEmail;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
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
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Customer User',
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

    private function payload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'type' => 'individual',
                'name' => 'Maria Silva',
                'legal_name' => null,
                'tax_country_code' => 'BR',
                'tax_identifier_type' => 'CPF',
                'tax_identifier' => '529.982.247-25',
                'responsible_user_id' => null,
                'tags' => [
                    'vip',
                    'varejo',
                ],
                'notes' => 'Cliente de teste.',
            ],
            $overrides
        );
    }

    public function test_customer_can_be_listed(): void
    {
        $tenant = $this->tenant(
            'customers-list'
        );

        $user = $this->user(
            $tenant,
            'list@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Cliente Visível',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-list.localhost/customers'
            );

        $response->assertOk();
        $response->assertSee(
            'Cliente Visível'
        );
    }

    public function test_individual_customer_can_be_created(): void
    {
        $tenant = $this->tenant(
            'customers-individual'
        );

        $user = $this->user(
            $tenant,
            'individual@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://customers-individual.localhost/customers',
                $this->payload()
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()
            ->where(
                'name',
                'Maria Silva'
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'customers.show',
                $customer->id
            )
        );

        $this->assertDatabaseHas(
            'customers',
            [
                'tenant_id' => $tenant->id,
                'type' => 'individual',
                'name' => 'Maria Silva',
                'tax_identifier_type' => 'CPF',
                'tax_identifier' => '52998224725',
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'customer.created',
            ]
        );
    }

    public function test_company_customer_can_be_created(): void
    {
        $tenant = $this->tenant(
            'customers-company'
        );

        $user = $this->user(
            $tenant,
            'company@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://customers-company.localhost/customers',
                $this->payload([
                    'type' => 'company',
                    'name' => 'Acme',
                    'legal_name' => 'Acme Comércio Ltda',
                    'tax_identifier_type' => 'CNPJ',
                    'tax_identifier' => '04.252.011/0001-10',
                ])
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()
            ->where(
                'name',
                'Acme'
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'customers.show',
                $customer->id
            )
        );

        $this->assertDatabaseHas(
            'customers',
            [
                'tenant_id' => $tenant->id,
                'type' => 'company',
                'name' => 'Acme',
                'tax_identifier_type' => 'CNPJ',
                'tax_identifier' => '04252011000110',
            ]
        );
    }

    public function test_invalid_cpf_is_rejected(): void
    {
        $tenant = $this->tenant(
            'customers-invalid-cpf'
        );

        $user = $this->user(
            $tenant,
            'invalid-cpf@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $response = $this
            ->actingAs($user)
            ->post(
                'http://customers-invalid-cpf.localhost/customers',
                $this->payload([
                    'tax_identifier' =>
                        '111.111.111-11',
                ])
            );

        $response->assertSessionHasErrors(
            'tax_identifier'
        );
    }

    public function test_responsible_must_belong_to_tenant(): void
    {
        $tenantA = $this->tenant(
            'customers-owner-a'
        );

        $tenantB = $this->tenant(
            'customers-owner-b'
        );

        $userA = $this->user(
            $tenantA,
            'creator@customers.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $userB = $this->user(
            $tenantB,
            'foreign-owner@customers.local'
        );

        $response = $this
            ->actingAs($userA)
            ->post(
                'http://customers-owner-a.localhost/customers',
                $this->payload([
                    'responsible_user_id' =>
                        $userB->id,
                ])
            );

        $response->assertSessionHasErrors(
            'responsible_user_id'
        );
    }

    public function test_customer_can_be_updated(): void
    {
        $tenant = $this->tenant(
            'customers-update'
        );

        $user = $this->user(
            $tenant,
            'update@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Antes',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "http://customers-update.localhost/customers/{$customer->id}",
                $this->payload([
                    'name' => 'Depois',
                ])
            );

        $response->assertRedirect(
            route(
                'customers.show',
                $customer->id
            )
        );

        $this->assertDatabaseHas(
            'customers',
            [
                'id' => $customer->id,
                'name' => 'Depois',
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'customer.updated',
            ]
        );
    }

    public function test_customer_can_be_deleted(): void
    {
        $tenant = $this->tenant(
            'customers-delete'
        );

        $user = $this->user(
            $tenant,
            'delete@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_DELETE
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Excluir',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                "http://customers-delete.localhost/customers/{$customer->id}"
            );

        $response->assertRedirect(
            route('customers.index')
        );

        $this->assertDatabaseMissing(
            'customers',
            [
                'id' => $customer->id,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' => $tenant->id,
                'action' => 'customer.deleted',
            ]
        );
    }

    public function test_other_tenant_customer_cannot_be_updated(): void
    {
        $tenantA = $this->tenant(
            'customers-cross-a'
        );

        $tenantB = $this->tenant(
            'customers-cross-b'
        );

        $userA = $this->user(
            $tenantA,
            'cross@customers.local'
        );

        $this->grant(
            $userA,
            PermissionEnum::CUSTOMERS_UPDATE
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $customerB = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Cliente B',
        ]);

        $response = $this
            ->actingAs($userA)
            ->put(
                "http://customers-cross-a.localhost/customers/{$customerB->id}",
                $this->payload()
            );

        $response->assertNotFound();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $tenant = $this->tenant(
            'customers-denied'
        );

        $user = $this->user(
            $tenant,
            'denied@customers.local'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-denied.localhost/customers'
            );

        $response->assertForbidden();
    }

    public function test_disabled_feature_blocks_customers(): void
    {
        $tenant = $this->tenant(
            'customers-feature-off'
        );

        $user = $this->user(
            $tenant,
            'feature@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CUSTOMERS,
            false
        );

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-feature-off.localhost/customers'
            );

        $response->assertForbidden();
    }

    public function test_customer_limit_blocks_creation(): void
    {
        $tenant = $this->tenant(
            'customers-limit'
        );

        $user = $this->user(
            $tenant,
            'limit@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        app(TenantCapabilities::class)->setLimit(
            $tenant,
            Feature::CUSTOMERS,
            1
        );

        app(TenantContext::class)->set(
            $tenant
        );

        Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Existente',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                'http://customers-limit.localhost/customers',
                $this->payload()
            );

        $response->assertSessionHasErrors(
            'limit'
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $this->assertSame(
            1,
            Customer::query()->count()
        );
    }

    public function test_tags_are_normalized(): void
    {
        $tenant = $this->tenant(
            'customers-tags'
        );

        $user = $this->user(
            $tenant,
            'tags@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                'http://customers-tags.localhost/customers',
                $this->payload([
                    'tags' => [
                        ' vip ',
                        'varejo',
                        'vip',
                    ],
                ])
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $customer = Customer::query()
            ->firstOrFail();

        $this->assertSame(
            [
                'vip',
                'varejo',
            ],
            $customer->tags
        );
    }

    public function test_list_can_filter_customer_type(): void
    {
        $tenant = $this->tenant(
            'customers-filter'
        );

        $user = $this->user(
            $tenant,
            'filter@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        Customer::create([
            'type' => CustomerType::INDIVIDUAL,
            'name' => 'Pessoa Física',
        ]);

        Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Pessoa Jurídica',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-filter.localhost/customers?type=company'
            );

        $response->assertOk();
        $response->assertSee(
            'Pessoa Jurídica'
        );

        $response->assertDontSee(
            'Pessoa Física'
        );
    }

    public function test_customer_list_is_paginated(): void
    {
        $tenant = $this->tenant(
            'customers-pagination'
        );

        $user = $this->user(
            $tenant,
            'pagination@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        for ($i = 1; $i <= 21; $i++) {
            Customer::create([
                'type' => CustomerType::INDIVIDUAL,
                'name' => sprintf(
                    'Cliente %02d',
                    $i
                ),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-pagination.localhost/customers'
            );

        $response->assertOk();

        $response->assertViewHas(
            'customers',
            fn ($customers) =>
                $customers->perPage() === 20
                && $customers->total() === 21
        );
    }

    public function test_customer_list_searches_related_email(): void
    {
        $tenant = $this->tenant(
            'customers-search-email'
        );

        $user = $this->user(
            $tenant,
            'search-email@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerA = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Email',
        ]);

        CustomerEmail::create([
            'customer_id' => $customerA->id,
            'email' => 'financeiro@empresa.local',
        ]);

        Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Outra',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-search-email.localhost/customers?search=financeiro@empresa.local'
            );

        $response->assertOk();
        $response->assertSee(
            'Empresa Email'
        );

        $response->assertDontSee(
            'Empresa Outra'
        );
    }

    public function test_customer_list_searches_related_phone(): void
    {
        $tenant = $this->tenant(
            'customers-search-phone'
        );

        $user = $this->user(
            $tenant,
            'search-phone@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerA = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Telefone',
        ]);

        CustomerPhone::create([
            'customer_id' => $customerA->id,
            'country_code' => 'BR',
            'national_number' => '85999999999',
        ]);

        Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Outra',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-search-phone.localhost/customers?search=(85)%2099999-9999'
            );

        $response->assertOk();
        $response->assertSee(
            'Empresa Telefone'
        );

        $response->assertDontSee(
            'Empresa Outra'
        );
    }

    public function test_customer_list_searches_contact_name(): void
    {
        $tenant = $this->tenant(
            'customers-search-contact'
        );

        $user = $this->user(
            $tenant,
            'search-contact@customers.local'
        );

        $this->grant(
            $user,
            PermissionEnum::CUSTOMERS_VIEW
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $customerA = Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Contato',
        ]);

        \App\Models\CustomerContact::create([
            'customer_id' => $customerA->id,
            'name' => 'João Compras',
            'type' => 'commercial',
        ]);

        Customer::create([
            'type' => CustomerType::COMPANY,
            'name' => 'Empresa Outra',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                'http://customers-search-contact.localhost/customers?search=Jo%C3%A3o%20Compras'
            );

        $response->assertOk();
        $response->assertSee(
            'Empresa Contato'
        );

        $response->assertDontSee(
            'Empresa Outra'
        );
    }
}
