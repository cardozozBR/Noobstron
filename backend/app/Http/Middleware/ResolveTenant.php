<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use App\Support\TenantGlobalSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        /*
         * Identifica o primeiro segmento do hostname como o slug
         * do tenant.
         *
         * Exemplos:
         * tenant-teste.localhost
         * tenant-teste.example.com
         */
        $parts = explode('.', $host);
        $slug = $parts[0] ?? null;

        if (!$slug || $slug === 'www' || $slug === 'localhost') {
            abort(404, 'Tenant not identified.');
        }

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            abort(404, 'Tenant not found.');
        }

        if (!TenantGlobalSettings::isValid(
            $tenant->country_code,
            $tenant->locale,
            $tenant->timezone,
            $tenant->currency
        )) {
            abort(500, 'Tenant global settings are invalid.');
        }

        /*
         * Disponibiliza o tenant para a requisição.
         */
        $request->attributes->set('tenant', $tenant);

        /*
         * Define o tenant atual da aplicação.
         */
        app(TenantContext::class)->set($tenant);

        /*
         * Aplica apenas o locale durante esta requisição.
         *
         * O timezone global da aplicação permanece em UTC.
         * Conversões para o timezone do tenant devem ser feitas
         * explicitamente através de TenantDateTime.
         */
        $previousLocale = app()->getLocale();

        app()->setLocale($tenant->locale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}