<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProposalService;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProposalPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
            'country_code' => 'BR',
            'locale' => 'pt-BR',
            'timezone' => 'America/Fortaleza',
            'currency' => 'BRL',
        ]);

        app(TenantContext::class)->set($tenant);

        return $tenant;
    }

    private function user(Tenant $tenant): User
    {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'PDF User',
            'email' => $tenant->slug . '-pdf@local',
            'password' => Hash::make('TesteSenha123'),
            'role' => 'user',
        ]);
    }

    private function grantView(User $user): void
    {
        $permission = Permission::query()
            ->where(
                'name',
                PermissionEnum::PROPOSALS_VIEW->value
            )
            ->firstOrFail();

        $user->permissions()
            ->syncWithoutDetaching($permission->id);
    }

    private function enable(Tenant $tenant): void
    {
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::PROPOSALS,
            true
        );
    }

    private function proposal(): Proposal
    {
        return app(ProposalService::class)->create([
            'number' => 'PROP-PDF-001',
            'notes' => 'PDF test',
            'items' => [
                [
                    'item_type' => 'service',
                    'name' => 'PDF Service',
                    'code' => 'PDF-SVC',
                    'quantity' => 2,
                    'unit_price_minor' => 5000,
                    'discount_minor' => 1000,
                    'taxes' => [
                        [
                            'code' => 'tax',
                            'amount_minor' => 500,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_pdf_requires_authentication(): void
    {
        $tenant = $this->tenant('proposal-pdf-auth');
        $this->enable($tenant);

        $proposal = $this->proposal();

        $this->get(
            "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/pdf"
        )->assertRedirect();
    }

    public function test_pdf_requires_view_permission(): void
    {
        $tenant = $this->tenant('proposal-pdf-permission');
        $this->enable($tenant);

        $user = $this->user($tenant);
        $proposal = $this->proposal();

        $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/pdf"
            )
            ->assertForbidden();
    }

    public function test_user_can_download_proposal_pdf(): void
    {
        $tenant = $this->tenant('proposal-pdf-download');
        $this->enable($tenant);

        $user = $this->user($tenant);
        $this->grantView($user);

        $proposal = $this->proposal();

        $response = $this
            ->actingAs($user)
            ->get(
                "http://{$tenant->slug}.localhost/proposals/{$proposal->id}/pdf"
            );

        $response->assertOk();

        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );

        $this->assertStringStartsWith(
            '%PDF',
            $response->getContent()
        );
    }

    public function test_other_tenant_pdf_cannot_be_downloaded(): void
    {
        $tenantA = $this->tenant('proposal-pdf-a');
        $this->enable($tenantA);

        $userA = $this->user($tenantA);
        $this->grantView($userA);

        $tenantB = $this->tenant('proposal-pdf-b');
        $this->enable($tenantB);

        $foreign = $this->proposal();

        app(TenantContext::class)->set($tenantA);

        $this
            ->actingAs($userA)
            ->get(
                "http://{$tenantA->slug}.localhost/proposals/{$foreign->id}/pdf"
            )
            ->assertNotFound();
    }
}
