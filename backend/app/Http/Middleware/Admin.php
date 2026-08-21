<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $tenant = $request->attributes->get('tenant');

        if (!$tenant || $user->tenant_id !== $tenant->id) {
            abort(403, 'Acesso negado para este tenant.');
        }

        if (!$user->isAdmin()) {
    abort(403, 'Acesso permitido somente para administradores.');
}

        return $next($request);
    }
}