<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ChargeService;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChargeHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_charge_routes_require_authentication(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-auth'
        );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        foreach ([
            ['get', '/charges'],
            ['get', '/charges/create'],
            ['post', '/charges'],
            [
                'post',
                "/charges/{$charge->id}/sent",
            ],
            [
                'post',
                "/charges/{$charge->id}/failed",
            ],
            [
                'post',
                "/charges/{$charge->id}/cancel",
            ],
        ] as [$method, $path]) {
            $this->{$method}(
                $this->url(
                    $tenant,
                    $path
                )
            )->assertRedirect();
        }
    }

    public function test_index_requires_charges_feature(): void
    {
        [$tenant] =
            $this->environment(
                'charge-http-feature'
            );

        $user = $this->user(
            $tenant,
            'charge-feature@local'
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_VIEW
        );

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CHARGES,
            false
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/charges'
                )
            )
            ->assertForbidden();
    }

    public function test_index_requires_view_permission(): void
    {
        [$tenant] =
            $this->environment(
                'charge-http-view'
            );

        $user = $this->user(
            $tenant,
            'charge-view@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/charges'
                )
            )
            ->assertForbidden();
    }

    public function test_user_can_access_charge_index(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-index'
        );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $user = $this->user(
            $tenant,
            'charge-index@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_VIEW
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/charges'
                )
            )
            ->assertOk()
            ->assertSee(
                (string) $charge->attempt
            )
            ->assertSee(
                $receivable->title
            );
    }

    public function test_create_requires_create_permission(): void
    {
        [$tenant] =
            $this->environment(
                'charge-http-create-permission'
            );

        $user = $this->user(
            $tenant,
            'charge-create-permission@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this
            ->actingAs($user)
            ->get(
                $this->url(
                    $tenant,
                    '/charges/create'
                )
            )
            ->assertForbidden();
    }

    public function test_store_creates_charge(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-store'
        );

        $user = $this->user(
            $tenant,
            'charge-store@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_CREATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    '/charges'
                ),
                [
                    'receivable_id' =>
                        $receivable->id,
                    'channel' => 'email',
                    'recipient' =>
                        'finance@example.com',
                ]
            )
            ->assertRedirect(
                route('charges.index')
            );

        $charge = Charge::query()
            ->firstOrFail();

        $this->assertSame(
            $receivable->id,
            $charge->receivable_id
        );

        $this->assertSame(
            'email',
            $charge->channel
        );
    }

    public function test_charge_can_be_marked_as_sent(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-sent'
        );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $user = $this->user(
            $tenant,
            'charge-sent@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_UPDATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/charges/{$charge->id}/sent"
                ),
                [
                    'external_reference' =>
                        'EXT-HTTP-001',
                ]
            )
            ->assertRedirect(
                route('charges.index')
            );

        $charge->refresh();

        $this->assertSame(
            ChargeStatus::SENT,
            $charge->status
        );

        $this->assertSame(
            'EXT-HTTP-001',
            $charge->external_reference
        );
    }

    public function test_charge_can_be_marked_as_failed(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-failed'
        );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $user = $this->user(
            $tenant,
            'charge-failed@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_UPDATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/charges/{$charge->id}/failed"
                ),
                [
                    'failure_reason' =>
                        'Provider unavailable',
                ]
            )
            ->assertRedirect(
                route('charges.index')
            );

        $this->assertSame(
            ChargeStatus::FAILED,
            $charge->refresh()->status
        );
    }

    public function test_charge_can_be_cancelled(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-http-cancel'
        );

        $charge = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $user = $this->user(
            $tenant,
            'charge-cancel@local'
        );

        $this->enableCharges(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::CHARGES_UPDATE
        );

        $this
            ->actingAs($user)
            ->post(
                $this->url(
                    $tenant,
                    "/charges/{$charge->id}/cancel"
                )
            )
            ->assertRedirect(
                route('charges.index')
            );

        $this->assertSame(
            ChargeStatus::CANCELLED,
            $charge->refresh()->status
        );
    }

    public function test_other_tenant_charge_cannot_be_changed(): void
    {
        [$tenantA] =
            $this->environment(
                'charge-http-tenant-a'
            );

        [
            ,
            $receivableB,
        ] = $this->environment(
            'charge-http-tenant-b'
        );

        $chargeB = app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
        ]);

        $userA = $this->user(
            $tenantA,
            'charge-tenant-a@local'
        );

        $this->enableCharges(
            $tenantA
        );

        $this->grant(
            $userA,
            PermissionEnum::CHARGES_UPDATE
        );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->post(
                $this->url(
                    $tenantA,
                    "/charges/{$chargeB->id}/sent"
                )
            )
            ->assertNotFound();
    }

    public function test_index_is_isolated_between_tenants(): void
    {
        [
            $tenantA,
            $receivableA,
        ] = $this->environment(
            'charge-http-index-a'
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableA->id,
                'recipient' =>
                    'tenant-a@example.com',
        ]);

        $userA = $this->user(
            $tenantA,
            'charge-index-a@local'
        );

        $this->enableCharges(
            $tenantA
        );

        $this->grant(
            $userA,
            PermissionEnum::CHARGES_VIEW
        );

        [
            ,
            $receivableB,
        ] = $this->environment(
            'charge-http-index-b'
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivableB->id,
            'recipient' =>
                'tenant-b@example.com',
        ]);

        app(TenantContext::class)->set(
            $tenantA
        );

        $this
            ->actingAs($userA)
            ->get(
                $this->url(
                    $tenantA,
                    '/charges'
                )
            )
            ->assertOk()
            ->assertSee(
                'tenant-a@example.com'
            )
            ->assertDontSee(
                'tenant-b@example.com'
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
            'tenant_id' =>
                $tenant->id,
            'type' => 'company',
            'name' => 'Cliente ' . $slug,
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

    private function user(
        Tenant $tenant,
        string $email
    ): User {
        app(TenantContext::class)->set(
            $tenant
        );

        return User::query()->create([
            'tenant_id' =>
                $tenant->id,
            'name' => 'Charge User',
            'email' => $email,
            'password' =>
                Hash::make(
                    'TesteSenha123'
                ),
            'role' => 'user',
        ]);
    }

    private function enableCharges(
        Tenant $tenant
    ): void {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::CHARGES,
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