<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function edit(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            abort(
                403,
                'As permissões de administradores não podem ser alteradas.'
            );
        }

        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        $userPermissionIds = $user->permissions()
            ->pluck('permissions.id')
            ->all();

        return view('users.permissions', [
            'user' => $user,
            'permissions' => $permissions,
            'userPermissionIds' => $userPermissionIds,
        ]);
    }

    public function update(
        Request $request,
        int $id,
        AuditService $audits
    ) {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            abort(
                403,
                'As permissões de administradores não podem ser alteradas.'
            );
        }

        $data = $request->validate([
            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        $oldPermissionIds = $user->permissions()
            ->pluck('permissions.id')
            ->sort()
            ->values()
            ->all();

        $newPermissionIds = collect($data['permissions'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $addedIds = array_values(array_diff(
            $newPermissionIds,
            $oldPermissionIds
        ));

        $removedIds = array_values(array_diff(
            $oldPermissionIds,
            $newPermissionIds
        ));

        $user->permissions()->sync($newPermissionIds);

        $addedPermissions = Permission::whereIn('id', $addedIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $removedPermissions = Permission::whereIn('id', $removedIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $description = 'Permissões atualizadas para o usuário: '
            . $user->name
            . ' ('
            . $user->email
            . ').';

        if (!empty($addedPermissions)) {
            $description .= ' Adicionadas: '
                . implode(', ', $addedPermissions)
                . '.';
        }

        if (!empty($removedPermissions)) {
            $description .= ' Removidas: '
                . implode(', ', $removedPermissions)
                . '.';
        }

        $audits->log(
            'user.permissions.updated',
            $description
        );

        return redirect()
            ->route('users.permissions.edit', $user->id)
            ->with(
                'success',
                'Permissões atualizadas com sucesso.'
            );
    }
}