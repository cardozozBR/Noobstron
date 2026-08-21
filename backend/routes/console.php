<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'activities:send-reminders',
    function (
        \App\Services\ActivityReminderService $service
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

\Illuminate\Support\Facades\Schedule::command(
    'activities:send-reminders'
)
    ->hourly()
    ->withoutOverlapping();
Artisan::command(
    'triggers:dispatch-overdue-receivables',
    function (
        \App\Services\ReceivableOverdueTenantRunner $runner
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

\Illuminate\Support\Facades\Schedule::command(
    'triggers:dispatch-overdue-receivables'
)
    ->hourly()
    ->withoutOverlapping();

Artisan::command(
    'trials:block-expired',
    function (
        \App\Services\TrialExpirationTenantRunner $runner
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

\Illuminate\Support\Facades\Schedule::command(
    'trials:block-expired'
)
    ->hourly()
    ->withoutOverlapping();
    \Illuminate\Support\Facades\Schedule::command(
    'subscriptions:sync-stripe-invoices'
)
    ->hourly()
    ->withoutOverlapping();
