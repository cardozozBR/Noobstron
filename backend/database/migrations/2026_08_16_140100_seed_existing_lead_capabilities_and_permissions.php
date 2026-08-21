<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            'leads.view' => 'Visualizar leads',
            'leads.create' => 'Criar leads',
            'leads.update' => 'Editar leads',
            'leads.delete' => 'Excluir leads',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $label,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $leadPermissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($permissions))
            ->pluck('id');

        $adminIds = DB::table('users')
            ->where('role', 'admin')
            ->pluck('id');

        foreach ($adminIds as $userId) {
            foreach ($leadPermissionIds as $permissionId) {
                DB::table('permission_user')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                    ],
                    []
                );
            }
        }

        $tenantIds = DB::table('tenants')
            ->pluck('id');

        foreach ($tenantIds as $tenantId) {
            DB::table('tenant_features')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'feature' => 'leads',
                ],
                [
                    'enabled' => true,
                    'limit_value' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $leadPermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'leads.view',
                'leads.create',
                'leads.update',
                'leads.delete',
            ])
            ->pluck('id');

        if ($leadPermissionIds->isNotEmpty()) {
            DB::table('permission_user')
                ->whereIn(
                    'permission_id',
                    $leadPermissionIds
                )
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', [
                'leads.view',
                'leads.create',
                'leads.update',
                'leads.delete',
            ])
            ->delete();

        DB::table('tenant_features')
            ->where('feature', 'leads')
            ->delete();
    }
};
