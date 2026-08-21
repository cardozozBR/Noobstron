<?php

namespace App\Http\Middleware;

use App\Enums\Permission as PermissionEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Permission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $tenant = $request->attributes->get('tenant');

        if (!$tenant || $user->tenant_id !== $tenant->id) {
            abort(403, 'Acesso negado para este tenant.');
        }

        $permissionEnum = PermissionEnum::tryFrom($permission);

        if (!$permissionEnum) {
            abort(500, 'Permissão inválida.');
        }

        if (!$user->hasPermission($permissionEnum)) {
            abort(403, 'Você não possui permissão para esta ação.');
        }

        return $next($request);
    }
}