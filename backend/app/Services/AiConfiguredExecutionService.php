<?php

namespace App\Services;

use App\Models\Tenant;
use App\Support\AiRequest;
use App\Support\AiResult;
use RuntimeException;

class AiConfiguredExecutionService
{
    public function __construct(
        private readonly AiProviderConfigurationResolver $configurations,
        private readonly AiProviderRegistry $providers,
        private readonly AiExecutionService $execution,
    ) {
    }

    public function execute(
        Tenant $tenant,
        AiRequest $request,
        ?string $provider = null
    ): AiResult {
        $configuration =
            $this->configurations
                ->resolve(
                    $provider
                );

        if (! $configuration->enabled) {
            throw new RuntimeException(
                "AI provider [{$configuration->normalizedProvider()}] is disabled."
            );
        }

        $resolvedProvider =
            $this->providers->resolve(
                $configuration
                    ->normalizedProvider()
            );

        $configuredRequest =
            $request->configured(
                model:
                    $configuration
                        ->normalizedModel(),
                parameters:
                    $configuration
                        ->parameters,
            );

        return $this->execution
            ->execute(
                tenant:
                    $tenant,
                provider:
                    $resolvedProvider,
                request:
                    $configuredRequest,
            );
    }
}