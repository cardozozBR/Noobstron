<?php

namespace App\Http\Controllers;

use App\Services\AiAssistantService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AiAssistantController extends Controller
{
    public function rewrite(
        Request $request,
        TenantContext $tenantContext,
        AiAssistantService $assistant
    ): JsonResponse {
        $tenant = $tenantContext->get();

        $user = $request->user();

        if (
            ! $user
            || (int) $user->tenant_id !== (int) $tenant->id
        ) {
            abort(
                403,
                'Acesso negado para este tenant.'
            );
        }

        $data = $request->validate([
            'text' => [
                'required',
                'string',
            ],

            'instruction' => [
                'nullable',
                'string',
            ],

            'estimated_tokens' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'provider' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $result = $assistant->rewrite(
            tenant:
                $tenant,

            text:
                $data['text'],

            instruction:
                $data['instruction']
                ?? null,

            estimatedTokens:
                $data['estimated_tokens']
                ?? 500,

            provider:
                $data['provider']
                ?? null,
        );

        return response()->json([
            'data' => [
                'content' =>
                    $result->content,

                'model' =>
                    $result->model,

                'usage' => [
                    'input_tokens' =>
                        $result->inputTokens,

                    'output_tokens' =>
                        $result->outputTokens,

                    'total_tokens' =>
                        $result->totalTokens,
                ],
            ],
        ]);
    }
}