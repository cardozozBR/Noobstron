<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Enums\ReceivableStatus;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceivableHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_receivable_routes_require_authentication(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-auth'
            );

        $receivable = $this->receivable(
            $customer
        );

        $this->get(
            $this->url(
                $tenant,
                '/receivables'
            )
        )->assertRedirect();

        $this->get(
            $this->url(
                $tenant,
                '/receivables/create'
            )
        )->assertRedirect();

        $this->post(
            $this->url(
                $tenant,
                '/receivables'
            )
        )->assertRedirect();

        $this->get(
            $this->url(
                $tenant,
                "/receivables/{$receivable->id}/edit"
            )
        )->assertRedirect();

        $this->put(
            $this->url(
                $tenant,
                "/receivables/{$receivable->id}"
            )
        )->assertRedirect();

        $this->post(
            $this->url(
                $tenant,
                "/receivables/{$receivable->id}/pay"
            )
        )->assertRedirect();

        $this->post(
            $this->url(
                $tenant,
                "/receivables/{$receivable->id}/cancel"
            )
        )->assertRedirect();
    }

    public function test_index_requires_receivables_feature(): void
    {
        [$tenant] =
            $this->environment(
                'receivables-feature'
            );

        $user = $this->user(
            $tenant,
            'feature@local'
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::RECEIVABLES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/receivables'
                )
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        [$tenant] =
            $this->environment(
                'receivables-view'
            );

        $user = $this->user(
            $tenant,
            'view@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/receivables'
                )
            )
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_index(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-index'
            );

        $user = $this->user(
            $tenant,
            'index@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_VIEW
        );

        $receivable = $this->receivable(
            $customer,
            [
                'title' =>
                    'RECEIVABLE-VISIBLE',
            ]
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/receivables'
                )
            )
            ->assertOk()
            ->assertSee(
                $receivable->title
            );
    }

    public function test_create_requires_create_permission(): void
    {
        [$tenant] =
            $this->environment(
                'receivables-create-permission'
            );

        $user = $this->user(
            $tenant,
            'create-permission@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/receivables/create'
                )
            )
            ->assertForbidden();
    }

    public function test_store_creates_receivable_in_current_tenant(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-store'
            );

        $user = $this->user(
            $tenant,
            'store@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    '/receivables'
                ),
                [
                    'customer_id' =>
                        $customer->id,
                    'title' =>
                        'Titulo HTTP',
                    'amount_minor' =>
                        123456,
                    'currency' =>
                        'BRL',
                    'due_date' =>
                        '2026-09-30',
                ]
            )
            ->assertRedirect(
                route(
                    'receivables.index'
                )
            );

        $receivable =
            Receivable::query()
                ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $receivable->tenant_id
        );

        $this->assertSame(
            $customer->id,
            $receivable->customer_id
        );

        $this->assertSame(
            'Titulo HTTP',
            $receivable->title
        );

        $this->assertSame(
            123456,
            $receivable->amount_minor
        );
    }

    public function test_edit_requires_update_permission(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-edit-permission'
            );

        $receivable = $this->receivable(
            $customer
        );

        $user = $this->user(
            $tenant,
            'edit-permission@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    "/receivables/{$receivable->id}/edit"
                )
            )
            ->assertForbidden();
    }

    public function test_receivable_can_be_updated(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-update'
            );

        $receivable = $this->receivable(
            $customer
        );

        $user = $this->user(
            $tenant,
            'update@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_UPDATE
        );

        $this
            ->actingAs($user)
            ->put(
                $this->url(
                    $tenant,
                    "/receivables/{$receivable->id}"
                ),
                [
                    'title' =>
                        'Titulo atualizado',
                ]
            )
            ->assertRedirect(
                route(
                    'receivables.index'
                )
            );

        $this->assertSame(
            'Titulo atualizado',
            $receivable
                ->refresh()
                ->title
        );
    }

    public function test_receivable_can_be_marked_as_paid(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-paid'
            );

        $receivable = $this->receivable(
            $customer
        );

        $user = $this->user(
            $tenant,
            'paid@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_UPDATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/receivables/{$receivable->id}/pay"
                ),
                [
                    'payment_reference' =>
                        'PIX-HTTP-001',
                ]
            )
            ->assertRedirect(
                route(
                    'receivables.index'
                )
            );

        $receivable->refresh();

        $this->assertSame(
            ReceivableStatus::PAID,
            $receivable->status
        );

        $this->assertNotNull(
            $receivable->paid_at
        );

        $this->assertSame(
            'PIX-HTTP-001',
            $receivable->payment_reference
        );
    }

    public function test_receivable_can_be_cancelled(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-cancel'
            );

        $receivable = $this->receivable(
            $customer
        );

        $user = $this->user(
            $tenant,
            'cancel@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_UPDATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/receivables/{$receivable->id}/cancel"
                )
            )
            ->assertRedirect(
                route(
                    'receivables.index'
                )
            );

        $this->assertSame(
            ReceivableStatus::CANCELLED,
            $receivable
                ->refresh()
                ->status
        );
    }

    public function test_other_tenant_receivable_cannot_be_edited(): void
    {
        [$tenantA] =
            $this->environment(
                'receivables-tenant-a'
            );

        [, $customerB] =
            $this->environment(
                'receivables-tenant-b'
            );

        $foreign = $this->receivable(
            $customerB
        );

        $userA = $this->user(
            $tenantA,
            'tenant-a@local'
        );

        $this->enableReceivables(
            $tenantA
        );

        $this->grant(
            $userA,
            PermissionEnum::RECEIVABLES_UPDATE
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                $this->url(
                    $tenantA,
                    "/receivables/{$foreign->id}/edit"
                )
            )
            ->assertNotFound();
    }

    public function test_other_tenant_customer_cannot_be_used(): void
    {
        [$tenantA] =
            $this->environment(
                'receivables-customer-a'
            );

        [, $customerB] =
            $this->environment(
                'receivables-customer-b'
            );

        $userA = $this->user(
            $tenantA,
            'customer-a@local'
        );

        $this->enableReceivables(
            $tenantA
        );

        $this->grant(
            $userA,
            PermissionEnum::RECEIVABLES_CREATE
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $url = $this->url(
            $tenantA,
            '/receivables'
        );

        $this
            ->actingAs($userA)
            ->from($url)
            ->post(
                $url,
                [
                    'customer_id' =>
                        $customerB->id,
                    'title' =>
                        'Foreign customer',
                    'amount_minor' =>
                        10000,
                    'due_date' =>
                        '2026-09-30',
                ]
            )
            ->assertRedirect()
            ->assertSessionHasErrors(
                'receivable'
            );

        $this->assertSame(
            0,
            Receivable::query()->count()
        );
    }

    public function test_index_is_isolated_between_tenants(): void
    {
        [$tenantA, $customerA] =
            $this->environment(
                'receivables-index-a'
            );

        $userA = $this->user(
            $tenantA,
            'index-a@local'
        );

        $this->enableReceivables(
            $tenantA
        );

        $this->grant(
            $userA,
            PermissionEnum::RECEIVABLES_VIEW
        );

        $this->receivable(
            $customerA,
            [
                'title' =>
                    'RECEIVABLE-TENANT-A',
            ]
        );

        [, $customerB] =
            $this->environment(
                'receivables-index-b'
            );

        $this->receivable(
            $customerB,
            [
                'title' =>
                    'RECEIVABLE-TENANT-B',
            ]
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                $this->url(
                    $tenantA,
                    '/receivables'
                )
            )
            ->assertOk()
            ->assertSee(
                'RECEIVABLE-TENANT-A'
            )
            ->assertDontSee(
                'RECEIVABLE-TENANT-B'
            );
    }

    private function environment(
        string $slug
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
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

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $slug,
        ]);

        return [
            $tenant,
            $customer,
        ];
    }

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Receivables User',
            'email' => $email,
            'password' => Hash::make(
                'TesteSenha123'
            ),
            'role' => 'user',
        ]);
    }

    private function enableReceivables(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::RECEIVABLES,
            true
        );
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
            ->syncWithoutDetaching([
                $model->id,
            ]);
    }

    private function receivable(
        Customer $customer,
        array $override = []
    ): Receivable {
        return app(
            ReceivableService::class
        )->create(
            array_merge(
                [
                    'customer_id' =>
                        $customer->id,
                    'title' =>
                        'Receivable HTTP',
                    'amount_minor' =>
                        100000,
                    'currency' =>
                        'BRL',
                    'due_date' =>
                        '2026-09-30',
                ],
                $override
            )
        );
    }

    private function url(
        Tenant $tenant,
        string $path
    ): string {
        return 'http://'
            . $tenant->slug
            . '.localhost'
            . $path;
    }
}