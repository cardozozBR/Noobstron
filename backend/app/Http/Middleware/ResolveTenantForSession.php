<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use App\Support\TenantGlobalSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantForSession
{
    /**
     * Seed TenantContext early enough for database-backed sessions.
     *
     * Public routes intentionally remove ResolveTenant. When one of those
     * routes is visited on a tenant hostname, Laravel's database session
     * handler may still resolve the authenticated web user while persisting
     * the session. The User model is tenant-scoped, so the context must be
     * available until StartSession finishes saving the response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $tenant = $this->tenantFromHost($request->getHost());

        if ($tenant !== null) {
            $tenantContext->set($tenant);
            $request->attributes->set('tenant', $tenant);
        }

        // TenantContext is registered as a scoped binding. Laravel owns the
        // lifecycle and flushes scoped instances when a new request / job
        // lifecycle starts. Clearing it manually here would make post-response
        // work (including database session persistence) race with tenant-scoped
        // model resolution and also makes HTTP integration tests lose context.
        return $next($request);
    }

    private function tenantFromHost(string $host): ?Tenant
    {
        $slug = explode('.', $host)[0] ?? '';

        if ($slug === '' || $slug === 'www' || $slug === 'localhost') {
            return null;
        }

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if ($tenant === null) {
            return null;
        }

        if (!TenantGlobalSettings::isValid(
            $tenant->country_code,
            $tenant->locale,
            $tenant->timezone,
            $tenant->currency,
        )) {
            return null;
        }

        return $tenant;
    }
}
