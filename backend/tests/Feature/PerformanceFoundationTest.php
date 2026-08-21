<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Performance Tenant',
            'slug' => 'performance-tenant',
            'status' => 'active',
        ]);
    }

    public function test_foundation_indexes_exist(): void
    {
        $indexes = collect(
            DB::select("
                SELECT name
                FROM sqlite_master
                WHERE type = 'index'
            ")
        )->pluck('name');

        $expected = [
            'users_tenant_id_name_index',
            'users_tenant_id_email_unique',
            'audit_logs_tenant_id_created_at_index',
            'audit_logs_tenant_action_created_at_index',
            'audit_logs_tenant_id_user_id_index',
            'tenant_features_tenant_id_feature_unique',
        ];

        foreach ($expected as $index) {
            $this->assertTrue(
                $indexes->contains($index),
                "Índice esperado não encontrado: {$index}"
            );
        }
    }

    public function test_dashboard_recent_logs_uses_bounded_query(): void
    {
        $tenant = $this->tenant();

        app(TenantContext::class)->set($tenant);

        for ($i = 1; $i <= 20; $i++) {
            AuditLog::create([
                'tenant_id' => $tenant->id,
                'action' => 'performance.test',
                'description' => 'Performance log ' . $i,
            ]);
        }

        $logs = AuditLog::query()
            ->latest()
            ->limit(10)
            ->get();

        $this->assertCount(10, $logs);
    }

    public function test_user_listing_can_be_ordered_by_indexed_name(): void
    {
        $tenant = $this->tenant();

        app(TenantContext::class)->set($tenant);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Zulu',
            'email' => 'zulu@performance.local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha',
            'email' => 'alpha@performance.local',
            'password' => 'TesteSenha123',
            'role' => 'user',
        ]);

        $users = User::query()
            ->orderBy('name')
            ->get();

        $this->assertSame(
            ['Alpha', 'Zulu'],
            $users->pluck('name')->all()
        );
    }
}