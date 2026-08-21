<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\TenantCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $slug): Tenant
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);

        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AUDIT,
            true
        );

        return $tenant;
    }
    private function user(Tenant $tenant, string $email): User
    {
        app(TenantContext::class)->set($tenant);

        return User::create([
            'name' => 'Usu?rio Teste',
            'email' => $email,
            'password' => Hash::make('TesteSenha123'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_audit_event_is_created(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant, 'user@tenant-a.local');

        app(TenantContext::class)->set($tenant);

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'test.created',
            'description' => 'Evento de teste.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'test.created',
        ]);
    }

    public function test_audit_filters_work(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant, 'user@tenant-a.local');

        app(TenantContext::class)->set($tenant);

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'user.created',
            'description' => 'Usu?rio criado.',
        ]);

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'login.success',
            'description' => 'Login realizado.',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-a.localhost/audit?action=user.created&search=Usu?rio');

        $response->assertOk();
        $response->assertSee('Usu?rio criado.');
        $response->assertDontSee('Login realizado.');
    }

    public function test_audit_logs_are_isolated_by_tenant(): void
    {
        $tenantA = $this->tenant('tenant-a');
        $tenantB = $this->tenant('tenant-b');

        $userA = $this->user($tenantA, 'user@tenant-a.local');

        app(TenantContext::class)->set($tenantB);

        AuditLog::create([
            'tenant_id' => $tenantB->id,
            'action' => 'tenant-b.event',
            'description' => 'Evento do tenant B.',
        ]);

        app(TenantContext::class)->set($tenantA);

        AuditLog::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $userA->id,
            'action' => 'tenant-a.event',
            'description' => 'Evento do tenant A.',
        ]);

        $response = $this
            ->actingAs($userA)
            ->get('http://tenant-a.localhost/audit');

        $response->assertOk();
        $response->assertSee('Evento do tenant A.');
        $response->assertDontSee('Evento do tenant B.');
    }

    public function test_system_event_can_exist_without_user(): void
    {
        $tenant = $this->tenant('tenant-a');

        app(TenantContext::class)->set($tenant);

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'action' => 'system.event',
            'description' => 'Evento do sistema.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'action' => 'system.event',
        ]);
    }

    public function test_audit_logs_are_paginated(): void
    {
        $tenant = $this->tenant('tenant-a');
        $user = $this->user($tenant, 'user@tenant-a.local');

        app(TenantContext::class)->set($tenant);

        AuditLog::query()->insert(
            collect(range(1, 20))->map(fn () => [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'test.pagination',
                'description' => 'Evento de pagina??o.',
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        $response = $this
            ->actingAs($user)
            ->get('http://tenant-a.localhost/audit');

        $response->assertOk();
        $response->assertSee('2');
    }
    public function test_audit_date_filter_respects_tenant_timezone(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Tenant Tokyo Audit',
            'slug' => 'tenant-tokyo-audit',
            'status' => 'active',
            'country_code' => 'JP',
            'locale' => 'ja',
            'timezone' => 'Asia/Tokyo',
            'currency' => 'JPY',
        ]);
        app(TenantCapabilities::class)->set(
            $tenant,
            Feature::AUDIT,
            true
        );

        $user = $this->user(
            $tenant,
            'admin@tenant-tokyo-audit.local'
        );

        app(\App\Services\TenantContext::class)->set($tenant);

        $inside = new \App\Models\AuditLog([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'action' => 'inside_day',
            'description' => 'Inside local day',
        ]);

        $inside->timestamps = false;
        $inside->created_at = '2026-08-15 15:30:00';
        $inside->updated_at = '2026-08-15 15:30:00';
        $inside->save();

        $outside = new \App\Models\AuditLog([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'action' => 'outside_day',
            'description' => 'Outside local day',
        ]);

        $outside->timestamps = false;
        $outside->created_at = '2026-08-16 15:00:00';
        $outside->updated_at = '2026-08-16 15:00:00';
        $outside->save();

        $response = $this
            ->actingAs($user)
            ->get(
                'http://tenant-tokyo-audit.localhost/audit?date_from=2026-08-16&date_to=2026-08-16'
            );

        $response->assertOk();

        $response->assertSee(
            $inside->description
        );

        $response->assertDontSee(
            $outside->description
        );
    }
}
