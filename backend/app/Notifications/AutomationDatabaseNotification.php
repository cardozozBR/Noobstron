<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AutomationDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly array $data = [],
    ) {
        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Notification title is required.'
            );
        }

        if (trim($this->message) === '') {
            throw new \InvalidArgumentException(
                'Notification message is required.'
            );
        }
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
        return 'automation';
    }

    public function toDatabase(
        object $notifiable
    ): array {
        return [
            'title' =>
                trim($this->title),

            'message' =>
                trim($this->message),

            'type' =>
                'automation',

            'data' =>
                $this->data,
        ];
    }
}