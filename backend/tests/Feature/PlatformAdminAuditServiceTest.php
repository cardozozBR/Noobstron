<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAuditLog;
use App\Models\Tenant;
use App\Services\PlatformAdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAdminAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_platform_admin_audit_log(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' => 'master@example.test',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Audit Test',
            'slug' => 'tenant-audit-test',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'platform');

        $log = app(
            PlatformAdminAuditService::class
        )->log(
            action: 'tenant.suspended',
            tenant: $tenant,
            entityType: Tenant::class,
            entityId: $tenant->id,
            beforeState: [
                'status' => 'active',
            ],
            afterState: [
                'status' => 'suspended',
            ],
            reason: 'Administrative test.',
        );

        $this->assertInstanceOf(
            PlatformAdminAuditLog::class,
            $log
        );

        $this->assertSame(
            $admin->id,
            $log->platform_admin_id
        );

        $this->assertSame(
            $tenant->id,
            $log->tenant_id
        );

        $this->assertSame(
            'tenant.suspended',
            $log->action
        );

        $this->assertSame(
            Tenant::class,
            $log->entity_type
        );

        $this->assertSame(
            (string) $tenant->id,
            $log->entity_id
        );

        $this->assertSame(
            ['status' => 'active'],
            $log->before_state
        );

        $this->assertSame(
            ['status' => 'suspended'],
            $log->after_state
        );

        $this->assertSame(
            PlatformAdminAuditService::RESULT_SUCCESS,
            $log->result
        );

        $this->assertSame(
            'Administrative test.',
            $log->reason
        );
    }

    public function test_it_can_record_failed_action(): void
    {
        $admin = PlatformAdmin::query()->create([
            'name' => 'Master Admin',
            'email' => 'master-failure@example.test',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'platform');

        $log = app(
            PlatformAdminAuditService::class
        )->log(
            action: 'webhook.retry',
            entityType: 'webhook',
            entityId: 'evt_test_123',
            result: PlatformAdminAuditService::RESULT_FAILURE,
            reason: 'Webhook retry failed.',
        );

        $this->assertSame(
            PlatformAdminAuditService::RESULT_FAILURE,
            $log->result
        );

        $this->assertSame(
            'Webhook retry failed.',
            $log->reason
        );

        $this->assertNull(
            $log->tenant_id
        );
    }
}
