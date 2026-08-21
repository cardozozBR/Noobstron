<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Tenant;
use App\Notifications\ActivityDueReminder;

class ActivityReminderService
{
    public function dispatchDueReminders(): int
    {
        $count = 0;

        Tenant::query()
            ->orderBy('id')
            ->each(
                function (
                    Tenant $tenant
                ) use (&$count): void {
                    app(
                        TenantContext::class
                    )->set($tenant);

                    $count +=
                        $this->dispatchForCurrentTenant();
                }
            );

        return $count;
    }

    public function dispatchForCurrentTenant(): int
    {
        $count = 0;

        Activity::query()
            ->with('responsible')
            ->where(
                'status',
                ActivityStatus::PENDING->value
            )
            ->whereNotNull(
                'responsible_user_id'
            )
            ->whereNotNull(
                'due_at'
            )
            ->whereNull(
                'reminder_notified_at'
            )
            ->whereBetween(
                'due_at',
                [
                    now(),
                    now()->addDay(),
                ]
            )
            ->orderBy('due_at')
            ->each(
                function (
                    Activity $activity
                ) use (&$count): void {
                    $responsible =
                        $activity->responsible;

                    if ($responsible === null) {
                        return;
                    }

                    $responsible->notify(
                        new ActivityDueReminder(
                            $activity
                        )
                    );

                    $activity
                        ->forceFill([
                            'reminder_notified_at' =>
                                now(),
                        ])
                        ->save();

                    $count++;
                }
            );

        return $count;
    }
}
