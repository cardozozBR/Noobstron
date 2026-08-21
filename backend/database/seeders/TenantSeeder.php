<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::updateOrCreate(
            ['slug' => 'tenant-a'],
            [
                'name' => 'Tenant A',
                'status' => 'active',
            ]
        );

        Tenant::updateOrCreate(
            ['slug' => 'tenant-b'],
            [
                'name' => 'Tenant B',
                'status' => 'active',
            ]
        );
    }
}