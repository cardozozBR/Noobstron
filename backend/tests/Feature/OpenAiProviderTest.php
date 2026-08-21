<?php

namespace Tests\Feature;

use App\Services\OpenAiProvider;
use App\Support\AiRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set(
            'ai.providers.openai.api_key',
            'test-openai-key'
        );

        config()->set(
            'ai.providers.openai.base_url',
            'https://api.openai.com/v1'
        );

        config()->set(
            'ai.providers.openai.timeout',
            30
        );
    }

    public function test_provider_code_is_openai(): void
    {
        $this->assertSame(
            'openai',
            app(
                OpenAiProvider::class
            )->code()
        );
    }

    public function test_response_api_request_is_sent_and_result_is_normalized(): void
    {
        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'id' =>
                        'resp_test',

                    'model' =>
                        'test-model',

                    'output' => [
                        [
                            'type' =>
                                'message',

                            'role' =>
                                'assistant',

                            'content' => [
                                [
                                    'type' =>
                                        'output_text',

                                    'text' =>
                                        'Hello from OpenAI.',
                                ],
                            ],
                        ],
                    ],

                    'usage' => [
                        'input_tokens' =>
                            12,

                        'output_tokens' =>
                            8,

                        'total_tokens' =>
                            20,
                    ],
                ], 200),
        ]);

        $result = app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Say hello.',
                estimatedTokens:
                    100,
                model:
                    'test-model',
            )
        );

        $this->assertSame(
            'Hello from OpenAI.',
            $result->content
        );

        $this->assertSame(
            'test-model',
            $result->model
        );

        $this->assertSame(
            12,
            $result->inputTokens
        );

        $this->assertSame(
            8,
            $result->outputTokens
        );

        $this->assertSame(
            20,
            $result->totalTokens
        );

        Http::assertSent(
            function (
                Request $request
            ): bool {
                return
                    $request->url()
                        ===
                    'https://api.openai.com/v1/responses'
                    &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer test-openai-key'
                    )
                    &&
                    $request[
                        'model'
                    ] === 'test-model'
                    &&
                    $request[
                        'input'
                    ] === 'Say hello.';
            }
        );
    }

    public function test_multiple_output_text_parts_are_combined(): void
    {
        Http::fake([
            '*' => Http::response([
                'model' =>
                    'test-model',

                'output' => [
                    [
                        'type' =>
                            'message',

                        'content' => [
                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'Hello ',
                            ],

                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'world',
                            ],
                        ],
                    ],
                ],

                'usage' => [
                    'input_tokens' =>
                        3,

                    'output_tokens' =>
                        2,
                ],
            ], 200),
        ]);

        $result = app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Test',
                estimatedTokens:
                    10,
                model:
                    'test-model',
            )
        );

        $this->assertSame(
            'Hello world',
            $result->content
        );
    }

    public function test_configured_parameters_are_forwarded(): void
    {
        Http::fake([
            '*' => Http::response([
                'model' =>
                    'test-model',

                'output' => [
                    [
                        'type' =>
                            'message',

                        'content' => [
                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'Configured.',
                            ],
                        ],
                    ],
                ],

                'usage' => [
                    'input_tokens' =>
                        5,

                    'output_tokens' =>
                        4,
                ],
            ], 200),
        ]);

        app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Configured',
                estimatedTokens:
                    20,
                model:
                    'test-model',
                parameters: [
                    'max_output_tokens' =>
                        250,
                ],
            )
        );

        Http::assertSent(
            function (
                Request $request
            ): bool {
                return
                    $request[
                        'max_output_tokens'
                    ] === 250
                    &&
                    $request[
                        'model'
                    ] === 'test-model';
            }
        );
    }

    public function test_request_parameters_cannot_override_model_or_input(): void
    {
        Http::fake([
            '*' => Http::response([
                'model' =>
                    'safe-model',

                'output' => [
                    [
                        'type' =>
                            'message',

                        'content' => [
                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'Safe.',
                            ],
                        ],
                    ],
                ],

                'usage' => [
                    'input_tokens' =>
                        1,

                    'output_tokens' =>
                        1,
                ],
            ], 200),
        ]);

        app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Safe input',
                estimatedTokens:
                    10,
                model:
                    'safe-model',
                parameters: [
                    'model' =>
                        'evil-model',

                    'input' =>
                        'evil-input',
                ],
            )
        );

        Http::assertSent(
            function (
                Request $request
            ): bool {
                return
                    $request[
                        'model'
                    ] === 'safe-model'
                    &&
                    $request[
                        'input'
                    ] === 'Safe input';
            }
        );
    }

    public function test_missing_api_key_is_rejected_before_http_request(): void
    {
        config()->set(
            'ai.providers.openai.api_key',
            null
        );

        Http::fake();

        try {
            app(
                OpenAiProvider::class
            )->execute(
                new AiRequest(
                    prompt:
                        'No key',
                    estimatedTokens:
                        10,
                    model:
                        'test-model',
                )
            );

            $this->fail(
                'Expected API key failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'OpenAI API key is not configured.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_missing_model_is_rejected_before_http_request(): void
    {
        Http::fake();

        try {
            app(
                OpenAiProvider::class
            )->execute(
                new AiRequest(
                    prompt:
                        'No model',
                    estimatedTokens:
                        10,
                )
            );

            $this->fail(
                'Expected model failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'OpenAI model is not configured.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_failed_http_response_is_propagated(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' =>
                        'Invalid request',
                ],
            ], 400),
        ]);

        $this->expectException(
            RequestException::class
        );

        app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Fail',
                estimatedTokens:
                    10,
                model:
                    'test-model',
            )
        );
    }

    public function test_empty_output_is_rejected(): void
    {
        Http::fake([
            '*' => Http::response([
                'model' =>
                    'test-model',

                'output' =>
                    [],

                'usage' => [
                    'input_tokens' =>
                        1,

                    'output_tokens' =>
                        0,
                ],
            ], 200),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Empty',
                estimatedTokens:
                    10,
                model:
                    'test-model',
            )
        );
    }

    public function test_missing_usage_is_rejected(): void
    {
        Http::fake([
            '*' => Http::response([
                'model' =>
                    'test-model',

                'output' => [
                    [
                        'type' =>
                            'message',

                        'content' => [
                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'No usage.',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'No usage',
                estimatedTokens:
                    10,
                model:
                    'test-model',
            )
        );
    }

    public function test_invalid_timeout_is_rejected_before_http_request(): void
    {
        config()->set(
            'ai.providers.openai.timeout',
            0
        );

        Http::fake();

        try {
            app(
                OpenAiProvider::class
            )->execute(
                new AiRequest(
                    prompt:
                        'Timeout',
                    estimatedTokens:
                        10,
                    model:
                        'test-model',
                )
            );

            $this->fail(
                'Expected timeout validation failure.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'OpenAI timeout is invalid.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_response_model_falls_back_to_requested_model(): void
    {
        Http::fake([
            '*' => Http::response([
                'output' => [
                    [
                        'type' =>
                            'message',

                        'content' => [
                            [
                                'type' =>
                                    'output_text',

                                'text' =>
                                    'Fallback.',
                            ],
                        ],
                    ],
                ],

                'usage' => [
                    'input_tokens' =>
                        2,

                    'output_tokens' =>
                        3,
                ],
            ], 200),
        ]);

        $result = app(
            OpenAiProvider::class
        )->execute(
            new AiRequest(
                prompt:
                    'Fallback',
                estimatedTokens:
                    10,
                model:
                    'requested-model',
            )
        );

        $this->assertSame(
            'requested-model',
            $result->model
        );
    }
}