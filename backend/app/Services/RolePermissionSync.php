<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSync
{
    public function sync(User $user): void
    {
        $permissionNames = match ($user->role) {
            Role::ADMIN => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.permissions',

                'leads.view',
                'leads.create',
                'leads.update',
                'leads.delete',

                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',

                'pipelines.view',
                'pipelines.create',
                'pipelines.update',
                'pipelines.delete',

                'opportunities.view',
                'opportunities.create',
                'opportunities.update',
                'opportunities.delete',

                'activities.view',
                'activities.create',
                'activities.update',
                'activities.delete',

                'catalog.view',
                'catalog.create',
                'catalog.update',
                'catalog.delete',

                'proposals.view',
                'proposals.create',
                'proposals.update',
                'proposals.delete',

                'sales.view',
                'sales.create',
                'sales.update',
                'sales.delete',

                'receivables.view',
                'receivables.create',
                'receivables.update',
                'receivables.delete',

                'charges.view',
                'charges.create',
                'charges.update',
                'charges.delete',
                'financial_indicators.view',
                'email.view',
                'email.create',
                'email.send',
                'email.templates',
                'whatsapp.view',
                'whatsapp.create',
                'whatsapp.send',
                'whatsapp.templates',
                'inbox.view',
                'inbox.assign',
                'inbox.manage',
                'imports.view',
                'imports.create',

                'audit.view',
                'settings.update',
                'ai.use',

                'profile.view',
                'profile.update',
            ],

            Role::USER => [

                'profile.view',
                'profile.update',
            ],
        };

        $permissionIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->all();

        $user->permissions()->sync($permissionIds);
    }
}
