<?php

namespace Tests\Feature;

use App\Enums\AutomationActionType;
use App\Jobs\ExecuteAutomationActionJob;
use App\Models\AuditLog;
use App\Models\AutomationActionExecution;
use App\Models\Tenant;
use App\Services\AutomationActionExecutionLedger;
use App\Services\AutomationActionExecutor;
use App\Services\AutomationActionQueueService;
use App\Services\TenantContext;
use App\Support\AutomationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationActionQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_executor_is_registered_with_all_action_handlers(): void
    {
        $executor = app(
            AutomationActionExecutor::class
        );

        foreach (
            AutomationActionType::cases()
            as $type
        ) {
            $this->assertTrue(
                $executor->has($type),
                'Missing handler for '
                . $type->value
            );
        }
    }

    public function test_job_has_three_attempts(): void
    {
        $job = $this->job(
            1,
            'retry-tries'
        );

        $this->assertSame(
            3,
            $job->tries
        );
    }

    public function test_job_has_progressive_backoff(): void
    {
        $job = $this->job(
            1,
            'retry-backoff'
        );

        $this->assertSame(
            [
                60,
                300,
            ],
            $job->backoff()
        );
    }

    public function test_action_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $tenant = $this->tenant(
            'automation-queue-dispatch'
        );

        $action = AutomationAction::make(
            $tenant->id,
            AutomationActionType::CREATE_TASK,
            [
                'title' =>
                    'Queued task',
            ]
        );

        $executionKey = app(
            AutomationActionQueueService::class
        )->dispatch(
            $action,
            [
                'trigger' => [
                    'name' =>
                        'lead.created',
                ],
            ],
            'queue-test-key'
        );

        $this->assertSame(
            'queue-test-key',
            $executionKey
        );

        Queue::assertPushed(
            ExecuteAutomationActionJob::class,
            function (
                ExecuteAutomationActionJob $job
            ) use ($tenant): bool {
                return
                    $job->tenantId
                        === $tenant->id
                    &&
                    $job->type
                        === AutomationActionType::CREATE_TASK->value
                    &&
                    $job->executionKey
                        === 'queue-test-key'
                    &&
                    $job->parameters['title']
                        === 'Queued task'
                    &&
                    $job->context['trigger']['name']
                        === 'lead.created';
            }
        );
    }

    public function test_dispatch_generates_execution_key_when_not_informed(): void
    {
        Queue::fake();

        $tenant = $this->tenant(
            'automation-queue-generated-key'
        );

        $key = app(
            AutomationActionQueueService::class
        )->dispatch(
            AutomationAction::make(
                $tenant->id,
                AutomationActionType::CREATE_TASK,
                [
                    'title' =>
                        'Generated key task',
                ]
            )
        );

        $this->assertNotSame(
            '',
            trim($key)
        );

        Queue::assertPushed(
            ExecuteAutomationActionJob::class,
            function (
                ExecuteAutomationActionJob $job
            ) use ($key): bool {
                return $job->executionKey
                    === $key;
            }
        );
    }

    public function test_job_restores_tenant_context_and_executes_action(): void
    {
        $tenant = $this->tenant(
            'automation-queue-execute'
        );

        app(
            TenantContext::class
        )->clear();

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'execute-once',
            parameters: [
                'title' =>
                    'Task from queue',
            ],
            context: [
                'source' =>
                    'queue-test',
            ],
        );

        $this->handle(
            $job
        );

        /*
         * O job deve sempre limpar o TenantContext
         * ao terminar.
         */
        try {
            app(
                TenantContext::class
            )->get();

            $this->fail(
                'Tenant context should have been cleared.'
            );
        } catch (\RuntimeException) {
            //
        }

        /*
         * As queries abaixo usam BelongsToTenant.
         * Portanto o teste restaura explicitamente
         * o tenant somente depois de validar o cleanup.
         */
        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $this->assertDatabaseHas(
            'activities',
            [
                'tenant_id' =>
                    $tenant->id,

                'title' =>
                    'Task from queue',
            ]
        );

        $execution =
            AutomationActionExecution::query()
                ->where(
                    'execution_key',
                    'execute-once'
                )
                ->firstOrFail();

        $this->assertNotNull(
            $execution->completed_at
        );
    }
    public function test_completed_job_is_idempotent_when_reexecuted(): void
    {
        $tenant = $this->tenant(
            'automation-queue-idempotent'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'same-job',
            parameters: [
                'title' =>
                    'Only once',
            ],
        );

        $this->handle($job);
        $this->handle($job);

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $activities = \App\Models\Activity::query()
            ->where(
                'title',
                'Only once'
            )
            ->get();

        $this->assertCount(
            1,
            $activities
        );

        $this->assertSame(
            1,
            AutomationActionExecution::query()
                ->where(
                    'execution_key',
                    'same-job'
                )
                ->count()
        );
    }

    public function test_incomplete_execution_can_be_retried(): void
    {
        $tenant = $this->tenant(
            'automation-queue-retry-incomplete'
        );

        $ledger = app(
            AutomationActionExecutionLedger::class
        );

        $execution = $ledger->claim(
            $tenant->id,
            'incomplete-job',
            AutomationActionType::CREATE_TASK->value
        );

        $this->assertNull(
            $execution->completed_at
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'incomplete-job',
            parameters: [
                'title' =>
                    'Recovered task',
            ],
        );

        $this->handle($job);

        $this->assertDatabaseHas(
            'activities',
            [
                'tenant_id' =>
                    $tenant->id,

                'title' =>
                    'Recovered task',
            ]
        );

        $execution->refresh();

        $this->assertNotNull(
            $execution->completed_at
        );
    }

    public function test_job_overrides_context_tenant_with_action_tenant(): void
    {
        $tenant = $this->tenant(
            'automation-queue-context'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'context-tenant',
            parameters: [
                'title' =>
                    'Context tenant task',
            ],
            context: [
                'tenant_id' =>
                    999999,
            ],
        );

        $this->handle($job);

        $this->assertDatabaseHas(
            'activities',
            [
                'tenant_id' =>
                    $tenant->id,

                'title' =>
                    'Context tenant task',
            ]
        );
    }

    public function test_missing_tenant_is_rejected(): void
    {
        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: 999999,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'missing-tenant',
            parameters: [
                'title' =>
                    'Missing tenant',
            ],
        );

        $this->handle($job);
    }

    public function test_job_logs_started_and_completed_execution(): void
    {
        Log::spy();

        $tenant = $this->tenant(
            'automation-log-success'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'log-success',
            parameters: [
                'title' =>
                    'Logged task',
            ],
        );

        $this->handle(
            $job
        );

        Log::shouldHaveReceived(
            'info'
        )
            ->with(
                'automation.action.started',
                \Mockery::on(
                    function (array $context) use ($tenant): bool {
                        return
                            $context['tenant_id']
                                === $tenant->id
                            &&
                            $context['execution_key']
                                === 'log-success'
                            &&
                            $context['action_type']
                                === AutomationActionType::CREATE_TASK->value
                            &&
                            count($context) === 3;
                    }
                )
            )
            ->once();

        Log::shouldHaveReceived(
            'info'
        )
            ->with(
                'automation.action.completed',
                \Mockery::on(
                    function (array $context) use ($tenant): bool {
                        return
                            $context['tenant_id']
                                === $tenant->id
                            &&
                            $context['execution_key']
                                === 'log-success'
                            &&
                            $context['action_type']
                                === AutomationActionType::CREATE_TASK->value
                            &&
                            count($context) === 3;
                    }
                )
            )
            ->once();
    }

    public function test_completed_redelivery_does_not_log_second_execution(): void
    {
        Log::spy();

        $tenant = $this->tenant(
            'automation-log-redelivery'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'log-redelivery',
            parameters: [
                'title' =>
                    'Logged once',
            ],
        );

        $this->handle(
            $job
        );

        $this->handle(
            $job
        );

        Log::shouldHaveReceived(
            'info'
        )
            ->with(
                'automation.action.started',
                \Mockery::type('array')
            )
            ->once();

        Log::shouldHaveReceived(
            'info'
        )
            ->with(
                'automation.action.completed',
                \Mockery::type('array')
            )
            ->once();
    }

    public function test_failed_hook_logs_structured_failure_without_payload(): void
    {
        Log::spy();

        $job = new ExecuteAutomationActionJob(
            tenantId: 321,
            type: AutomationActionType::SEND_EMAIL->value,
            executionKey: 'log-failure',
            parameters: [
                'to' =>
                    'sensitive@example.test',

                'body' =>
                    'Sensitive body',
            ],
            context: [
                'secret' =>
                    'sensitive-context',
            ],
        );

        $exception =
            new \RuntimeException(
                'Expected execution failure.'
            );

        $job->failed(
            $exception
        );

        Log::shouldHaveReceived(
            'error'
        )
            ->once()
            ->with(
                'automation.action.failed',
                \Mockery::on(
                    function (array $context): bool {
                        return
                            $context['tenant_id'] === 321
                            &&
                            $context['execution_key']
                                === 'log-failure'
                            &&
                            $context['action_type']
                                === AutomationActionType::SEND_EMAIL->value
                            &&
                            $context['exception_class']
                                === \RuntimeException::class
                            &&
                            $context['exception_message']
                                === 'Expected execution failure.'
                            &&
                            ! array_key_exists(
                                'parameters',
                                $context
                            )
                            &&
                            ! array_key_exists(
                                'context',
                                $context
                            )
                            &&
                            count($context) === 5;
                    }
                )
            );
    }
    public function test_successful_execution_is_audited(): void
    {
        $tenant = $this->tenant(
            'automation-audit-success'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'audit-success',
            parameters: [
                'title' =>
                    'Audited task',
            ],
        );

        $this->handle(
            $job
        );

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'tenant_id' =>
                    $tenant->id,

                'user_id' =>
                    null,

                'action' =>
                    'automation.action.completed',

                'description' =>
                    'execution_key=audit-success; action_type=create_task',
            ]
        );
    }

    public function test_completed_redelivery_does_not_create_second_audit(): void
    {
        $tenant = $this->tenant(
            'automation-audit-redelivery'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: 'audit-redelivery',
            parameters: [
                'title' =>
                    'Audit once',
            ],
        );

        $this->handle(
            $job
        );

        $this->handle(
            $job
        );

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $count = AuditLog::query()
            ->where(
                'action',
                'automation.action.completed'
            )
            ->where(
                'description',
                'execution_key=audit-redelivery; action_type=create_task'
            )
            ->count();

        $this->assertSame(
            1,
            $count
        );
    }

    public function test_failed_hook_is_audited_without_sensitive_payload(): void
    {
        $tenant = $this->tenant(
            'automation-audit-failed'
        );

        $job = new ExecuteAutomationActionJob(
            tenantId: $tenant->id,
            type: AutomationActionType::SEND_EMAIL->value,
            executionKey: 'audit-failure',
            parameters: [
                'to' =>
                    'secret@example.test',

                'body' =>
                    'Sensitive content',
            ],
            context: [
                'token' =>
                    'secret-token',
            ],
        );

        app(
            TenantContext::class
        )->clear();

        $job->failed(
            new \RuntimeException(
                'Provider unavailable'
            )
        );

        app(
            TenantContext::class
        )->set(
            $tenant
        );

        $audit = AuditLog::query()
            ->where(
                'action',
                'automation.action.failed'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $audit->tenant_id
        );

        $this->assertNull(
            $audit->user_id
        );

        $this->assertSame(
            'execution_key=audit-failure; action_type=send_email; error=Provider unavailable',
            $audit->description
        );

        $this->assertStringNotContainsString(
            'secret@example.test',
            $audit->description
        );

        $this->assertStringNotContainsString(
            'Sensitive content',
            $audit->description
        );

        $this->assertStringNotContainsString(
            'secret-token',
            $audit->description
        );
    }
    private function handle(
        ExecuteAutomationActionJob $job
    ): void {
        $job->handle(
            app(TenantContext::class),
            app(AutomationActionExecutor::class),
            app(
                AutomationActionExecutionLedger::class
            ),
            app(
                \App\Services\AuditService::class
            )
        );
    }

    private function job(
        int $tenantId,
        string $executionKey
    ): ExecuteAutomationActionJob {
        return new ExecuteAutomationActionJob(
            tenantId: $tenantId,
            type: AutomationActionType::CREATE_TASK->value,
            executionKey: $executionKey,
            parameters: [
                'title' =>
                    'Configuration',
            ],
        );
    }

    private function tenant(
        string $slug
    ): Tenant {
        $tenant = Tenant::query()
            ->create([
                'name' =>
                    'Tenant ' . $slug,

                'slug' =>
                    $slug,

                'status' =>
                    'active',

                'country_code' =>
                    'BR',

                'locale' =>
                    'pt-BR',

                'timezone' =>
                    'America/Fortaleza',

                'currency' =>
                    'BRL',
            ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return $tenant;
    }
}