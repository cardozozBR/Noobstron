<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Support\AiRequest;
use App\Support\AiResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProvider
{
    public function code(): string
    {
        return 'openai';
    }

    public function execute(
        AiRequest $request
    ): AiResult {
        $apiKey = config(
            'ai.providers.openai.api_key'
        );

        if (
            ! is_string($apiKey)
            || trim($apiKey) === ''
        ) {
            throw new RuntimeException(
                'OpenAI API key is not configured.'
            );
        }

        $model = $request->model;

        if (
            ! is_string($model)
            || trim($model) === ''
        ) {
            throw new RuntimeException(
                'OpenAI model is not configured.'
            );
        }

        $baseUrl = config(
            'ai.providers.openai.base_url',
            'https://api.openai.com/v1'
        );

        if (
            ! is_string($baseUrl)
            || trim($baseUrl) === ''
        ) {
            throw new RuntimeException(
                'OpenAI base URL is not configured.'
            );
        }

        $timeout = config(
            'ai.providers.openai.timeout',
            30
        );

        if (
            ! is_int($timeout)
            || $timeout <= 0
        ) {
            throw new RuntimeException(
                'OpenAI timeout is invalid.'
            );
        }

        /*
         * Provider-specific parameters may be configured,
         * but protected request fields always win.
         */
        $payload = $request->parameters;

        unset(
            $payload['model'],
            $payload['input']
        );

        $payload['model'] =
            trim($model);

        $payload['input'] =
            $request->prompt;

        $response = Http::withToken(
            trim($apiKey)
        )
            ->acceptJson()
            ->asJson()
            ->timeout(
                $timeout
            )
            ->post(
                rtrim(
                    $baseUrl,
                    '/'
                ) . '/responses',
                $payload
            );

        $response->throw();

        return $this->toResult(
            $response,
            trim($model)
        );
    }

    private function toResult(
        Response $response,
        string $requestedModel
    ): AiResult {
        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException(
                'OpenAI response payload is invalid.'
            );
        }

        $content = $this->extractText(
            $data
        );

        if ($content === '') {
            throw new RuntimeException(
                'OpenAI response contains no text output.'
            );
        }

        $inputTokens = data_get(
            $data,
            'usage.input_tokens'
        );

        $outputTokens = data_get(
            $data,
            'usage.output_tokens'
        );

        if (
            ! is_int($inputTokens)
            || $inputTokens < 0
        ) {
            throw new RuntimeException(
                'OpenAI input token usage is invalid.'
            );
        }

        if (
            ! is_int($outputTokens)
            || $outputTokens < 0
        ) {
            throw new RuntimeException(
                'OpenAI output token usage is invalid.'
            );
        }

        $responseModel = data_get(
            $data,
            'model'
        );

        $model = (
            is_string($responseModel)
            && trim($responseModel) !== ''
        )
            ? trim($responseModel)
            : $requestedModel;

        return new AiResult(
            content:
                $content,
            model:
                $model,
            inputTokens:
                $inputTokens,
            outputTokens:
                $outputTokens,
        );
    }

    private function extractText(
        array $data
    ): string {
        $texts = [];

        $output = $data[
            'output'
        ] ?? [];

        if (! is_array($output)) {
            return '';
        }

        foreach ($output as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (
                ($item['type'] ?? null)
                !== 'message'
            ) {
                continue;
            }

            $parts = $item[
                'content'
            ] ?? [];

            if (! is_array($parts)) {
                continue;
            }

            foreach ($parts as $part) {
                if (! is_array($part)) {
                    continue;
                }

                if (
                    ($part['type'] ?? null)
                    !== 'output_text'
                ) {
                    continue;
                }

                $text = $part[
                    'text'
                ] ?? null;

                if (
                    is_string($text)
                    && $text !== ''
                ) {
                    $texts[] =
                        $text;
                }
            }
        }

        return trim(
            implode(
                '',
                $texts
            )
        );
    }
}