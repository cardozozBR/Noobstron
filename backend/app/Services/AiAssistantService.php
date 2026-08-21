<?php

namespace App\Services;

use App\Enums\Feature;
use App\Models\Tenant;
use App\Support\AiRequest;
use App\Support\AiResult;
use App\Support\TenantCapabilities;
use InvalidArgumentException;
use RuntimeException;

final class AiAssistantService
{
    public function __construct(
        private readonly TenantCapabilities $capabilities,
        private readonly AiConfiguredExecutionService $execution,
    ) {
    }

    public function rewrite(
        Tenant $tenant,
        string $text,
        ?string $instruction = null,
        int $estimatedTokens = 500,
        ?string $provider = null
    ): AiResult {
        $this->assertAvailable($tenant);

        $normalizedText = trim($text);

        if ($normalizedText === '') {
            throw new InvalidArgumentException(
                'Text to rewrite is required.'
            );
        }

        if ($estimatedTokens < 0) {
            throw new InvalidArgumentException(
                'Estimated AI tokens cannot be negative.'
            );
        }

        $normalizedInstruction = $instruction === null
            ? null
            : trim($instruction);

        if ($normalizedInstruction === '') {
            $normalizedInstruction = null;
        }

        $prompt = $this->rewritePrompt(
            text: $normalizedText,
            instruction: $normalizedInstruction,
        );

        return $this->execution->execute(
            tenant: $tenant,
            request: new AiRequest(
                prompt: $prompt,
                estimatedTokens: $estimatedTokens,
            ),
            provider: $provider,
        );
    }

    private function assertAvailable(
        Tenant $tenant
    ): void {
        if (! $this->capabilities->enabled(
            $tenant,
            Feature::AI
        )) {
            throw new RuntimeException(
                'AI feature is not enabled for tenant.'
            );
        }
    }

    private function rewritePrompt(
        string $text,
        ?string $instruction
    ): string {
        $parts = [
            'Rewrite the text below.',
            'Preserve its factual meaning.',
            'Return only the rewritten text.',
        ];

        if ($instruction !== null) {
            $parts[] = 'Additional instruction: ' . $instruction;
        }

        $parts[] = "Text:\n" . $text;

        return implode(
            "\n\n",
            $parts
        );
    }
}