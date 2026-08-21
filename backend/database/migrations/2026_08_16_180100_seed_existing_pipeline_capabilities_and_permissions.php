<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'pipelines.view' => 'Visualizar pipelines',
            'pipelines.create' => 'Criar pipelines',
            'pipelines.update' => 'Atualizar pipelines',
            'pipelines.delete' => 'Excluir pipelines',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $label,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $tenants = DB::table('tenants')
            ->select('id')
            ->get();

        foreach ($tenants as $tenant) {
            DB::table('tenant_features')->updateOrInsert(
                [
                    'tenant_id' => $tenant->id,
                    'feature' => 'pipelines',
                ],
                [
                    'enabled' => true,
                    'limit_value' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('permission_user')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($permissions))
            ->pluck('id');

        $admins = DB::table('users')
            ->where('role', 'admin')
            ->select('id')
            ->get();

        foreach ($admins as $admin) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_user')->insertOrIgnore([
                    'user_id' => $admin->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'pipelines.view',
            'pipelines.create',
            'pipelines.update',
            'pipelines.delete',
        ];

        if (Schema::hasTable('permission_user')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('name', $names)
                ->pluck('id');

            DB::table('permission_user')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('tenant_features')
            ->where('feature', 'pipelines')
            ->delete();

        DB::table('permissions')
            ->whereIn('name', $names)
            ->delete();
    }
};
