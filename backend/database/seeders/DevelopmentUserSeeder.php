<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenantContext = app(TenantContext::class);
        $rolePermissionSync = app(RolePermissionSync::class);

        $tenants = Tenant::query()
            ->whereIn('slug', ['tenant-a', 'tenant-b'])
            ->get()
            ->keyBy('slug');

        foreach ($tenants as $tenant) {
            $tenantContext->set($tenant);

            $admin = User::updateOrCreate(
                ['email' => "admin@{$tenant->slug}.local"],
                [
                    'name' => "Administrador {$tenant->name}",
                    'password' => 'TesteSenha123',
                    'role' => 'admin',
                ]
            );

            $rolePermissionSync->sync($admin);

            $user = User::updateOrCreate(
                ['email' => "usuario@{$tenant->slug}.local"],
                [
                    'name' => "Usuário {$tenant->name}",
                    'password' => 'TesteSenha123',
                    'role' => 'user',
                ]
            );

            $rolePermissionSync->sync($user);
        }

        $tenantContext->clear();
    }
}