<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'activities.view' =>
                'Visualizar atividades',
            'activities.create' =>
                'Criar atividades',
            'activities.update' =>
                'Atualizar atividades',
            'activities.delete' =>
                'Excluir atividades',
        ];

        foreach ($permissions as $name => $label) {
            DB::table('permissions')
                ->updateOrInsert(
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

        $tenants = DB::table('tenants')
            ->select('id')
            ->get();

        foreach ($tenants as $tenant) {
            DB::table('tenant_features')
                ->updateOrInsert(
                    [
                        'tenant_id' =>
                            $tenant->id,
                        'feature' =>
                            'activities',
                    ],
                    [
                        'enabled' => true,
                        'limit_value' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        }

        if (
            ! Schema::hasTable(
                'permission_user'
            )
        ) {
            return;
        }

        $permissionIds = DB::table(
            'permissions'
        )
            ->whereIn(
                'name',
                array_keys(
                    $permissions
                )
            )
            ->pluck('id');

        $admins = DB::table('users')
            ->where(
                'role',
                'admin'
            )
            ->select('id')
            ->get();

        foreach ($admins as $admin) {
            foreach (
                $permissionIds
                as $permissionId
            ) {
                DB::table(
                    'permission_user'
                )->insertOrIgnore([
                    'user_id' =>
                        $admin->id,
                    'permission_id' =>
                        $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
        ];

        if (
            Schema::hasTable(
                'permission_user'
            )
        ) {
            $permissionIds = DB::table(
                'permissions'
            )
                ->whereIn(
                    'name',
                    $names
                )
                ->pluck('id');

            DB::table('permission_user')
                ->whereIn(
                    'permission_id',
                    $permissionIds
                )
                ->delete();
        }

        DB::table('tenant_features')
            ->where(
                'feature',
                'activities'
            )
            ->delete();

        DB::table('permissions')
            ->whereIn(
                'name',
                $names
            )
            ->delete();
    }
};
