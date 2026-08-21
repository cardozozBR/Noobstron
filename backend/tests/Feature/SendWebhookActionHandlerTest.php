<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Services\AutomationActionExecutor;
use App\Services\SendWebhookActionHandler;
use App\Support\AutomationAction;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SendWebhookActionHandlerTest extends TestCase
{
    public function test_webhook_is_sent_as_json_post(): void
    {
        Http::fake([
            'https://example.test/*' =>
                Http::response(
                    [
                        'ok' => true,
                    ],
                    200
                ),
        ]);

        $result = app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::SEND_WEBHOOK,
                [
                    'url' =>
                        'https://example.test/hooks/automation',

                    'headers' => [
                        'X-Test' =>
                            'automation',
                    ],

                    'payload' => [
                        'event' =>
                            'opportunity.updated',

                        'id' =>
                            123,
                    ],
                ]
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            'https://example.test/hooks/automation',
            $result->data['url']
        );

        $this->assertSame(
            200,
            $result->data['status']
        );

        Http::assertSent(
            function ($request): bool {
                return
                    $request->method() === 'POST'
                    &&
                    $request->url()
                        === 'https://example.test/hooks/automation'
                    &&
                    $request->hasHeader(
                        'X-Test',
                        'automation'
                    )
                    &&
                    $request['event']
                        === 'opportunity.updated'
                    &&
                    $request['id'] === 123;
            }
        );
    }

    public function test_executor_can_send_webhook(): void
    {
        Http::fake([
            'https://example.test/*' =>
                Http::response(
                    [],
                    204
                ),
        ]);

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_WEBHOOK,
            app(
                SendWebhookActionHandler::class
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                10,
                AutomationActionType::SEND_WEBHOOK,
                [
                    'url' =>
                        'https://example.test/hook',

                    'payload' => [
                        'hello' =>
                            'world',
                    ],
                ]
            ),
            [
                'tenant_id' =>
                    10,
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            204,
            $result->data['status']
        );

        Http::assertSentCount(
            1
        );
    }

    public function test_url_is_required(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::SEND_WEBHOOK,
                []
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );
    }

    public function test_invalid_url_is_rejected_without_request(): void
    {
        Http::fake();

        try {
            app(
                SendWebhookActionHandler::class
            )->handle(
                AutomationAction::make(
                    1,
                    AutomationActionType::SEND_WEBHOOK,
                    [
                        'url' =>
                            'not-a-url',
                    ]
                ),
                [
                    'tenant_id' =>
                        1,
                ]
            );

            $this->fail(
                'Invalid URL should have been rejected.'
            );
        } catch (RuntimeException) {
            Http::assertNothingSent();
        }
    }

    public function test_non_http_scheme_is_rejected_without_request(): void
    {
        Http::fake();

        try {
            app(
                SendWebhookActionHandler::class
            )->handle(
                AutomationAction::make(
                    1,
                    AutomationActionType::SEND_WEBHOOK,
                    [
                        'url' =>
                            'ftp://example.test/file',
                    ]
                ),
                [
                    'tenant_id' =>
                        1,
                ]
            );

            $this->fail(
                'Non HTTP URL should have been rejected.'
            );
        } catch (RuntimeException) {
            Http::assertNothingSent();
        }
    }

    public function test_payload_must_be_array(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::SEND_WEBHOOK,
                [
                    'url' =>
                        'https://example.test/hook',

                    'payload' =>
                        'invalid',
                ]
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );
    }

    public function test_headers_must_be_array(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::SEND_WEBHOOK,
                [
                    'url' =>
                        'https://example.test/hook',

                    'headers' =>
                        'invalid',
                ]
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );
    }

    public function test_http_failure_returns_failure_result(): void
    {
        Http::fake([
            'https://example.test/*' =>
                Http::response(
                    [
                        'error' =>
                            'unavailable',
                    ],
                    503
                ),
        ]);

        $result = app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::SEND_WEBHOOK,
                [
                    'url' =>
                        'https://example.test/hook',
                ]
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );

        $this->assertFalse(
            $result->successful
        );

        $this->assertStringContainsString(
            '503',
            $result->error
        );
    }

    public function test_executor_rejects_tenant_mismatch_before_request(): void
    {
        Http::fake();

        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_WEBHOOK,
            app(
                SendWebhookActionHandler::class
            )
        );

        try {
            $executor->execute(
                AutomationAction::make(
                    10,
                    AutomationActionType::SEND_WEBHOOK,
                    [
                        'url' =>
                            'https://example.test/hook',
                    ]
                ),
                [
                    'tenant_id' =>
                        20,
                ]
            );

            $this->fail(
                'Tenant mismatch should have been rejected.'
            );
        } catch (RuntimeException) {
            Http::assertNothingSent();
        }
    }

    public function test_wrong_action_type_is_rejected(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class
        );

        app(
            SendWebhookActionHandler::class
        )->handle(
            AutomationAction::make(
                1,
                AutomationActionType::CREATE_TASK,
                [
                    'url' =>
                        'https://example.test/hook',
                ]
            ),
            [
                'tenant_id' =>
                    1,
            ]
        );
    }
}