<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'sales.view' => 'Visualizar vendas',
            'sales.create' => 'Criar vendas',
            'sales.update' => 'Atualizar vendas',
            'sales.delete' => 'Excluir vendas',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')->updateOrInsert(
                [
                    'name' => $name,
                ],
                [
                    'label' => $label,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach (
            DB::table('tenants')
                ->select('id')
                ->get()
            as $tenant
        ) {
            DB::table('tenant_features')->updateOrInsert(
                [
                    'tenant_id' => $tenant->id,
                    'feature' => 'sales',
                ],
                [
                    'enabled' => true,
                    'limit_value' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('permission_user')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn(
                'name',
                array_keys($permissions)
            )
            ->pluck('id');

        $admins = DB::table('users')
            ->where('role', 'admin')
            ->select('id')
            ->get();

        foreach ($admins as $admin) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_user')->updateOrInsert(
                    [
                        'user_id' => $admin->id,
                        'permission_id' => $permissionId,
                    ],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        //
    }
};
