<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'imports.view' => 'Visualizar importações',
            'imports.create' => 'Criar importações',
        ];

        foreach ($permissions as $name => $label) {
            $existing = DB::table('permissions')
                ->where('name', $name)
                ->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('name', $name)
                    ->update([
                        'label' => $label,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('permissions')->insert([
                'name' => $name,
                'label' => $label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tenants = DB::table('tenants')
            ->select('id')
            ->get();

        foreach ($tenants as $tenant) {
            DB::table('tenant_features')
                ->updateOrInsert(
                    [
                        'tenant_id' => $tenant->id,
                        'feature' => 'imports',
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
                DB::table('permission_user')
                    ->insertOrIgnore([
                        'user_id' => $admin->id,
                        'permission_id' => $permissionId,
                    ]);
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'imports.view',
            'imports.create',
        ];

        if (Schema::hasTable('permission_user')) {
            $permissionIds = DB::table('permissions')
                ->whereIn(
                    'name',
                    $permissionNames
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
            ->where('feature', 'imports')
            ->delete();

        DB::table('permissions')
            ->whereIn(
                'name',
                $permissionNames
            )
            ->delete();
    }
};
