<?php

namespace App\Services;

use App\Models\AiUsageRecord;
use App\Models\Tenant;
use InvalidArgumentException;

class AiUsageRecorder
{
    public function record(
        Tenant $tenant,
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        ?string $operation = null,
    ): AiUsageRecord {
        $provider = strtolower(trim($provider));
        $model = trim($model);
        $operation = $operation === null
            ? null
            : trim($operation);

        if ($provider === '') {
            throw new InvalidArgumentException(
                'AI provider is required.'
            );
        }

        if ($model === '') {
            throw new InvalidArgumentException(
                'AI model is required.'
            );
        }

        if (
            $inputTokens < 0
            || $outputTokens < 0
        ) {
            throw new InvalidArgumentException(
                'AI token usage cannot be negative.'
            );
        }

        if ($operation === '') {
            $operation = null;
        }

        return AiUsageRecord::query()
            ->forceCreate([
                'tenant_id' => $tenant->id,
                'provider' => $provider,
                'model' => $model,
                'operation' => $operation,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' =>
                    $inputTokens + $outputTokens,
            ]);
    }
}