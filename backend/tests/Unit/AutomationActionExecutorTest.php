<?php

namespace Tests\Unit;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Services\AutomationActionExecutor;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AutomationActionExecutorTest extends TestCase
{
    public function test_action_catalog_contains_expected_values(): void
    {
        $this->assertSame(
            [
                'create_task',
                'send_email',
                'send_whatsapp',
                'change_stage',
                'assign_responsible',
                'create_notification',
                'send_webhook',
            ],
            array_map(
                static fn (
                    AutomationActionType $type
                ): string =>
                    $type->value,
                AutomationActionType::cases()
            )
        );
    }

    public function test_action_can_be_created_from_string_type(): void
    {
        $action = AutomationAction::make(
            10,
            'send_email',
            [
                'to' =>
                    'customer@example.test',
            ]
        );

        $this->assertSame(
            10,
            $action->tenantId
        );

        $this->assertSame(
            AutomationActionType::SEND_EMAIL,
            $action->type
        );

        $this->assertSame(
            'customer@example.test',
            $action->parameters['to']
        );
    }

    public function test_action_requires_valid_tenant(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        AutomationAction::make(
            0,
            AutomationActionType::CREATE_TASK
        );
    }

    public function test_invalid_action_type_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        AutomationAction::make(
            1,
            'invalid_action'
        );
    }

    public function test_handler_can_be_registered(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_TASK,
            $this->handler()
        );

        $this->assertTrue(
            $executor->has(
                AutomationActionType::CREATE_TASK
            )
        );

        $this->assertFalse(
            $executor->has(
                AutomationActionType::SEND_EMAIL
            )
        );
    }

    public function test_matching_handler_executes_action(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::SEND_EMAIL,
            $this->handler(
                [
                    'message_id' => 123,
                ]
            )
        );

        $result = $executor->execute(
            AutomationAction::make(
                5,
                AutomationActionType::SEND_EMAIL,
                [
                    'to' =>
                        'client@example.test',
                ]
            ),
            [
                'tenant_id' => 5,
                'trigger' => [
                    'name' =>
                        'lead.created',
                ],
            ]
        );

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            123,
            $result->data['message_id']
        );
    }

    public function test_missing_handler_is_rejected(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        (new AutomationActionExecutor())
            ->execute(
                AutomationAction::make(
                    1,
                    AutomationActionType::SEND_WEBHOOK
                )
            );
    }

    public function test_context_tenant_must_match_action_tenant(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_TASK,
            $this->handler()
        );

        $this->expectException(
            RuntimeException::class
        );

        $executor->execute(
            AutomationAction::make(
                10,
                AutomationActionType::CREATE_TASK
            ),
            [
                'tenant_id' => 20,
            ]
        );
    }

    public function test_numeric_string_context_tenant_is_supported(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_TASK,
            $this->handler()
        );

        $result = $executor->execute(
            AutomationAction::make(
                10,
                AutomationActionType::CREATE_TASK
            ),
            [
                'tenant_id' => '10',
            ]
        );

        $this->assertTrue(
            $result->successful
        );
    }

    public function test_invalid_context_tenant_is_rejected(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_TASK,
            $this->handler()
        );

        $this->expectException(
            RuntimeException::class
        );

        $executor->execute(
            AutomationAction::make(
                10,
                AutomationActionType::CREATE_TASK
            ),
            [
                'tenant_id' =>
                    'invalid',
            ]
        );
    }

    public function test_context_without_tenant_remains_supported(): void
    {
        $executor =
            new AutomationActionExecutor();

        $executor->register(
            AutomationActionType::CREATE_NOTIFICATION,
            $this->handler()
        );

        $result = $executor->execute(
            AutomationAction::make(
                10,
                AutomationActionType::CREATE_NOTIFICATION
            ),
            []
        );

        $this->assertTrue(
            $result->successful
        );
    }

    public function test_success_result_preserves_data(): void
    {
        $result =
            AutomationActionResult::success([
                'id' => 99,
            ]);

        $this->assertTrue(
            $result->successful
        );

        $this->assertSame(
            99,
            $result->data['id']
        );

        $this->assertNull(
            $result->error
        );
    }

    public function test_failure_result_preserves_error(): void
    {
        $result =
            AutomationActionResult::failure(
                'Provider unavailable',
                [
                    'retryable' => true,
                ]
            );

        $this->assertFalse(
            $result->successful
        );

        $this->assertSame(
            'Provider unavailable',
            $result->error
        );

        $this->assertTrue(
            $result->data['retryable']
        );
    }

    private function handler(
        array $data = []
    ): AutomationActionHandler {
        return new class(
            $data
        ) implements AutomationActionHandler {
            public function __construct(
                private readonly array $data
            ) {
            }

            public function handle(
                AutomationAction $action,
                array $context
            ): AutomationActionResult {
                return AutomationActionResult::success(
                    $this->data
                );
            }
        };
    }
}