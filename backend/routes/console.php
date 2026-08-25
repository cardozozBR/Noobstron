<?php

use App\Jobs\WorkerHeartbeatJob;
use App\Services\ActivityReminderService;
use App\Services\ReceivableOverdueTenantRunner;
use App\Services\TrialExpirationTenantRunner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'activities:send-reminders',
    function (
        ActivityReminderService $service
    ): int {
        $count =
            $service->dispatchDueReminders();

        $this->info(
            "Activity reminders sent: {$count}"
        );

        return self::SUCCESS;
    }
)->purpose(
    'Send reminders for activities due within 24 hours'
);

Schedule::command(
    'activities:send-reminders'
)
    ->hourly()
    ->withoutOverlapping();
Artisan::command(
    'triggers:dispatch-overdue-receivables',
    function (
        ReceivableOverdueTenantRunner $runner
    ): int {
        $count =
            $runner->dispatch();

        $this->info(
            "Receivable overdue triggers dispatched: {$count}"
        );

        return self::SUCCESS;
    }
)->purpose(
    'Dispatch overdue receivable triggers for active tenants'
);

Schedule::command(
    'triggers:dispatch-overdue-receivables'
)
    ->hourly()
    ->withoutOverlapping();

Artisan::command(
    'trials:block-expired',
    function (
        TrialExpirationTenantRunner $runner
    ): int {
        $count = $runner->dispatch();

        $this->info(
            "Expired trials blocked: {$count}"
        );

        return self::SUCCESS;
    }
)->purpose(
    'Block active tenants with expired trials'
);

Schedule::command(
    'trials:block-expired'
)
    ->hourly()
    ->withoutOverlapping();
Schedule::command(
    'subscriptions:sync-stripe-invoices'
)
    ->hourly()
    ->withoutOverlapping();

Schedule::call(
    function (): void {
        cache()->put(
            'platform.scheduler.last_run_at',
            now()->toIso8601String(),
            now()->addHours(2)
        );
    }
)
    ->everyMinute()
    ->name('platform-scheduler-heartbeat')
    ->withoutOverlapping();

Schedule::job(
    new WorkerHeartbeatJob
)
    ->everyMinute()
    ->name('platform-worker-heartbeat')
    ->withoutOverlapping();
