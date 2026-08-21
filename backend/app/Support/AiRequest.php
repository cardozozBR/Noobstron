<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class AiRequest
{
    public function __construct(
        public string $prompt,
        public int $estimatedTokens,
        public ?string $model = null,
        public array $parameters = [],
    ) {
        if (
            trim(
                $this->prompt
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'AI prompt is required.'
            );
        }

        if ($this->estimatedTokens < 0) {
            throw new InvalidArgumentException(
                'Estimated AI tokens cannot be negative.'
            );
        }

        if (
            $this->model !== null
            && trim(
                $this->model
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'AI model cannot be blank.'
            );
        }
    }

    public function configured(
        string $model,
        array $parameters = []
    ): self {
        $normalizedModel = trim(
            $model
        );

        if ($normalizedModel === '') {
            throw new InvalidArgumentException(
                'AI model is required.'
            );
        }

        return new self(
            prompt:
                $this->prompt,
            estimatedTokens:
                $this->estimatedTokens,
            model:
                $normalizedModel,
            parameters:
                $parameters,
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