<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WorkerHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        cache()->put(
            'platform.worker.last_seen_at',
            now()->toIso8601String(),
            now()->addHours(2)
        );
    }
}
