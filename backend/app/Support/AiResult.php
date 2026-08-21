<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class AiResult
{
    public int $totalTokens;

    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
    ) {
        if (trim($this->model) === '') {
            throw new InvalidArgumentException(
                'AI result model is required.'
            );
        }

        if ($this->inputTokens < 0) {
            throw new InvalidArgumentException(
                'AI input tokens cannot be negative.'
            );
        }

        if ($this->outputTokens < 0) {
            throw new InvalidArgumentException(
                'AI output tokens cannot be negative.'
            );
        }

        $this->totalTokens =
            $this->inputTokens
            + $this->outputTokens;
    }
}