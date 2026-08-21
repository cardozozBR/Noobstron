<?php

namespace App\Services;

use App\Contracts\TriggerListener;
use App\Support\TriggerOccurrence;
use InvalidArgumentException;

class TriggerDispatcher
{
    /**
     * @var array<string, list<TriggerListener>>
     */
    private array $listeners = [];

    public function listen(
        string $triggerName,
        TriggerListener $listener
    ): void {
        $triggerName = trim($triggerName);

        if ($triggerName === '') {
            throw new InvalidArgumentException(
                'Trigger name is required.'
            );
        }

        $this->listeners[$triggerName][] =
            $listener;
    }

    public function dispatch(
        TriggerOccurrence $occurrence
    ): void {
        foreach (
            $this->listeners[$occurrence->name()] ?? []
            as $listener
        ) {
            $listener->handle($occurrence);
        }
    }
}
