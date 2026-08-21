<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class AiProviderConfiguration
{
    public function __construct(
        public string $provider,
        public string $model,
        public bool $enabled = true,
        public array $parameters = [],
    ) {
        if (
            trim(
                $this->provider
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'AI provider is required.'
            );
        }

        if (
            trim(
                $this->model
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'AI model is required.'
            );
        }
    }

    public function normalizedProvider(): string
    {
        return strtolower(
            trim(
                $this->provider
            )
        );
    }

    public function normalizedModel(): string
    {
        return trim(
            $this->model
        );
    }

    public function parameter(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->parameters[
            $key
        ] ?? $default;
    }
}