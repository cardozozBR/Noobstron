<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            'customers.view' => 'Visualizar clientes',
            'customers.create' => 'Criar clientes',
            'customers.update' => 'Editar clientes',
            'customers.delete' => 'Excluir clientes',
        ];

        foreach ($permissions as $name => $label) {
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                DB::table('permissions')
                    ->where('name', $name)
                    ->update([
                        'label' => $label,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $permissionIds = DB::table('permissions')
            ->whereIn(
                'name',
                array_keys($permissions)
            )
            ->pluck('id');

        $adminIds = DB::table('users')
            ->where('role', 'admin')
            ->pluck('id');

        foreach ($adminIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('permission_user')
                    ->where('user_id', $userId)
                    ->where(
                        'permission_id',
                        $permissionId
                    )
                    ->exists();

                if (!$exists) {
                    DB::table('permission_user')->insert([
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        $tenantIds = DB::table('tenants')
            ->pluck('id');

        foreach ($tenantIds as $tenantId) {
            $existing = DB::table('tenant_features')
                ->where('tenant_id', $tenantId)
                ->where('feature', 'customers')
                ->first();

            if ($existing) {
                DB::table('tenant_features')
                    ->where('id', $existing->id)
                    ->update([
                        'enabled' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('tenant_features')->insert([
                'tenant_id' => $tenantId,
                'feature' => 'customers',
                'enabled' => true,
                'limit_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_user')
                ->whereIn(
                    'permission_id',
                    $permissionIds
                )
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', [
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
            ])
            ->delete();

        DB::table('tenant_features')
            ->where('feature', 'customers')
            ->delete();
    }
};
