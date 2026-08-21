<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
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

class ChargeUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_charge_index_uses_tenant_locale(): void
    {
        [
            $tenant,
            $receivable,
        ] = $this->environment(
            'charge-ui-en',
            'en'
        );

        app(
            ChargeService::class
        )->create([
            'receivable_id' =>
                $receivable->id,
        ]);

        $user = $this->user(
            $tenant,
            'charge-ui-en@local'
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
            ->assertSee('Charges')
            ->assertSee(
                'Track billing attempts, deliveries, and failures.'
            )
            ->assertSee('Pending');
    }

    public function test_create_form_uses_translations(): void
    {
        [$tenant] =
            $this->environment(
                'charge-ui-form'
            );

        $user = $this->user(
            $tenant,
            'charge-ui-form@local'
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
            ->get(
                $this->url(
                    $tenant,
                    '/charges/create'
                )
            )
            ->assertOk()
            ->assertSee(
                __('charges.create_title')
            )
            ->assertSee(
                __('charges.fields.receivable')
            )
            ->assertSee(
                __('charges.fields.channel')
            );
    }

    public function test_navigation_shows_charges_when_allowed(): void
    {
        [$tenant] =
            $this->environment(
                'charge-ui-nav'
            );

        $user = $this->user(
            $tenant,
            'charge-ui-nav@local'
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
                __('charges.navigation')
            );
    }

    private function environment(
        string $slug,
        string $locale = 'pt-BR'
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant ' . $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => $locale,
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
            'name' => 'Charge UI User',
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