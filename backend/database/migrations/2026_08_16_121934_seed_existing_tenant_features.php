<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenantIds = DB::table('tenants')
            ->pluck('id');

        $features = [
            'users',
            'audit',
            'branding',
        ];

        foreach ($tenantIds as $tenantId) {
            foreach ($features as $feature) {
                DB::table('tenant_features')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'feature' => $feature,
                    ],
                    [
                        'enabled' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('tenant_features')
            ->whereIn('feature', [
                'users',
                'audit',
                'branding',
            ])
            ->delete();
    }
};