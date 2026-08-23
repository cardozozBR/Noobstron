<?php

namespace App\Http\Middleware;

use App\Exceptions\FeatureUnavailableException;

use App\Enums\Feature as FeatureEnum;
use App\Services\TenantWriteAccessService;
use App\Support\TenantCapabilities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Feature
{
    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            abort(403, 'Tenant não identificado.');
        }

        $featureEnum = FeatureEnum::tryFrom($feature);

        if (!$featureEnum) {
            abort(500, 'Feature inválida.');
        }

        $enabled = app(TenantCapabilities::class)
            ->enabled(
                $tenant,
                $featureEnum
            );

        if (!$enabled) {
            throw new FeatureUnavailableException($feature);
        }

        if (
            $featureEnum !== FeatureEnum::AI
            && !$request->isMethodSafe()
            && !app(TenantWriteAccessService::class)
                ->allowed($tenant)
        ) {
            abort(
                403,
                'Assinatura inativa. Consulte seus dados ou assine novamente para fazer alterações.'
            );
        }

        return $next($request);
    }
}