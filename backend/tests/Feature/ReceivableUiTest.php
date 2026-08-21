<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReceivableService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceivableUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_receivables_index_uses_tenant_locale(): void
    {
        [$tenant, $customer] =
            $this->environment(
                'receivables-ui-en',
                'en'
            );

        $user = $this->user(
            $tenant,
            'receivables-ui-en@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_VIEW
        );

        app(
            ReceivableService::class
        )->create([
            'customer_id' => $customer->id,
            'title' => 'English receivable',
            'amount_minor' => 10000,
            'due_date' => '2026-09-30',
        ]);

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
                'Accounts receivable'
            )
            ->assertSee(
                'Track customer receivables, due dates, and payments.'
            )
            ->assertSee(
                'Pending'
            );
    }

    public function test_create_form_uses_translations(): void
    {
        [$tenant] =
            $this->environment(
                'receivables-ui-create'
            );

        $user = $this->user(
            $tenant,
            'receivables-ui-create@local'
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
            ->get(
                $this->url(
                    $tenant,
                    '/receivables/create'
                )
            )
            ->assertOk()
            ->assertSee(
                __('receivables.create_title')
            )
            ->assertSee(
                __('receivables.fields.customer')
            )
            ->assertSee(
                __('receivables.fields.amount_minor')
            )
            ->assertSee(
                __('receivables.fields.due_date')
            );
    }

    public function test_navigation_shows_receivables_when_allowed(): void
    {
        [$tenant] =
            $this->environment(
                'receivables-ui-navigation'
            );

        $user = $this->user(
            $tenant,
            'receivables-ui-navigation@local'
        );

        $this->enableReceivables(
            $tenant
        );

        $this->grant(
            $user,
            PermissionEnum::RECEIVABLES_VIEW
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
                __('receivables.navigation')
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
            'name' => 'Receivables UI User',
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