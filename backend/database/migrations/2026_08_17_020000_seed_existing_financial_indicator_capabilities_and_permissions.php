<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            [
                'name' =>
                    'financial_indicators.view',
            ],
            [
                'label' =>
                    'Visualizar indicadores financeiros',
                'created_at' =>
                    now(),
                'updated_at' =>
                    now(),
            ]
        );

        foreach (
            DB::table('tenants')
                ->select('id')
                ->get()
            as $tenant
        ) {
            DB::table('tenant_features')->updateOrInsert(
                [
                    'tenant_id' =>
                        $tenant->id,
                    'feature' =>
                        'financial_indicators',
                ],
                [
                    'enabled' =>
                        true,
                    'limit_value' =>
                        null,
                    'created_at' =>
                        now(),
                    'updated_at' =>
                        now(),
                ]
            );
        }

        if (! Schema::hasTable('permission_user')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where(
                'name',
                'financial_indicators.view'
            )
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        $admins = DB::table('users')
            ->where(
                'role',
                'admin'
            )
            ->pluck('id');

        foreach ($admins as $adminId) {
            DB::table('permission_user')->updateOrInsert(
                [
                    'user_id' =>
                        $adminId,
                    'permission_id' =>
                        $permissionId,
                ],
                []
            );
        }
    }

    public function down(): void
    {
        //
    }
};