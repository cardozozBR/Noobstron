<?php

namespace App\Services;

use App\Support\AiProviderConfiguration;
use RuntimeException;

class AiProviderConfigurationResolver
{
    public function resolve(
        ?string $provider = null
    ): AiProviderConfiguration {
        $providerCode = $provider;

        if (
            $providerCode === null
            || trim(
                $providerCode
            ) === ''
        ) {
            $providerCode = config(
                'ai.default'
            );
        }

        if (
            ! is_string(
                $providerCode
            )
            || trim(
                $providerCode
            ) === ''
        ) {
            throw new RuntimeException(
                'Default AI provider is not configured.'
            );
        }

        $normalizedProvider =
            strtolower(
                trim(
                    $providerCode
                )
            );

        $configuration = config(
            'ai.providers.'
            . $normalizedProvider
        );

        if (
            ! is_array(
                $configuration
            )
        ) {
            throw new RuntimeException(
                "AI provider configuration [{$normalizedProvider}] was not found."
            );
        }

        $enabled = $configuration[
            'enabled'
        ] ?? false;

        if (! is_bool($enabled)) {
            throw new RuntimeException(
                "AI provider [{$normalizedProvider}] enabled flag is invalid."
            );
        }

        $model = $configuration[
            'model'
        ] ?? null;

        if (
            ! is_string(
                $model
            )
            || trim(
                $model
            ) === ''
        ) {
            throw new RuntimeException(
                "AI provider [{$normalizedProvider}] model is not configured."
            );
        }

        $parameters = $configuration[
            'parameters'
        ] ?? [];

        if (! is_array($parameters)) {
            throw new RuntimeException(
                "AI provider [{$normalizedProvider}] parameters are invalid."
            );
        }

        return new AiProviderConfiguration(
            provider:
                $normalizedProvider,
            model:
                trim(
                    $model
                ),
            enabled:
                $enabled,
            parameters:
                $parameters,
        );
    }
}