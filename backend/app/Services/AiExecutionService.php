<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Tenant;
use App\Support\AiRequest;
use App\Support\AiResult;
use RuntimeException;

class AiExecutionService
{
    public function __construct(
        private readonly AiUsageGuard $guard,
        private readonly AiUsageRecorder $recorder,
    ) {
    }

    public function execute(
        Tenant $tenant,
        AiProvider $provider,
        AiRequest $request
    ): AiResult {
        $providerCode = strtolower(
            trim(
                $provider->code()
            )
        );

        if ($providerCode === '') {
            throw new RuntimeException(
                'AI provider code is required.'
            );
        }

        /*
         * Quota must be validated before the provider
         * is allowed to execute any external work.
         */
        $this->guard->assertCanRequest(
            $tenant,
            $request->estimatedTokens
        );

        $result = $provider->execute(
            $request
        );

        /*
         * Estimated tokens are never persisted.
         * Only the real usage returned by the provider
         * becomes billable/observable tenant usage.
         */
        $this->recorder->record(
            tenant: $tenant,
            provider: $providerCode,
            model: $result->model,
            inputTokens: $result->inputTokens,
            outputTokens: $result->outputTokens,
        );

        return $result;
    }
}