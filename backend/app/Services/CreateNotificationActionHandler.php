<?php

namespace App\Services;

use App\Contracts\AutomationActionHandler;
use App\Enums\AutomationActionType;
use App\Models\User;
use App\Notifications\AutomationDatabaseNotification;
use App\Support\AutomationAction;
use App\Support\AutomationActionResult;
use RuntimeException;

class CreateNotificationActionHandler implements AutomationActionHandler
{
    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult {
        if (
            $action->type
            !== AutomationActionType::CREATE_NOTIFICATION
        ) {
            throw new RuntimeException(
                'Invalid action type for create notification handler.'
            );
        }

        $userId = $this->requiredPositiveInt(
            $action->parameters,
            'user_id'
        );

        $title = trim(
            (string) (
                $action->parameters['title']
                ?? ''
            )
        );

        if ($title === '') {
            throw new RuntimeException(
                'Notification title is required.'
            );
        }

        $message = trim(
            (string) (
                $action->parameters['message']
                ?? ''
            )
        );

        if ($message === '') {
            throw new RuntimeException(
                'Notification message is required.'
            );
        }

        $data =
            $action->parameters['data']
            ?? [];

        if (! is_array($data)) {
            throw new RuntimeException(
                'Notification data must be an array.'
            );
        }

        $user = User::query()
            ->findOrFail(
                $userId
            );

        $user->notify(
            new AutomationDatabaseNotification(
                $title,
                $message,
                $data
            )
        );

        $notification = $user
            ->notifications()
            ->latest()
            ->first();

        if ($notification === null) {
            throw new RuntimeException(
                'Notification was not persisted.'
            );
        }

        return AutomationActionResult::success([
            'notification_id' =>
                $notification->id,

            'user_id' =>
                (int) $user->id,

            'read_at' =>
                $notification->read_at,
        ]);
    }

    private function requiredPositiveInt(
        array $parameters,
        string $key
    ): int {
        $value =
            $parameters[$key] ?? null;

        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        throw new RuntimeException(
            "Automation action parameter {$key} is required."
        );
    }
}