<?php

namespace App\Contracts;

use App\Support\TriggerOccurrence;

interface TriggerListener
{
    public function handle(
        TriggerOccurrence $occurrence
    ): void;
}
