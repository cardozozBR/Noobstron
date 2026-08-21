<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Notifications\Notification;

class ActivityDueReminder extends Notification
{
    public function __construct(
        public readonly Activity $activity
    ) {
    }

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }

    public function databaseType(
        object $notifiable
    ): string {
        return 'activity_due_reminder';
    }

    public function toDatabase(
        object $notifiable
    ): array {
        return [
            'activity_id' =>
                $this->activity->id,

            'title' =>
                $this->activity->title,

            'type' =>
                $this->activity->type->value,

            'due_at' =>
                $this->activity->due_at
                    ?->toIso8601String(),

            'customer_id' =>
                $this->activity->customer_id,

            'opportunity_id' =>
                $this->activity->opportunity_id,
        ];
    }
}
