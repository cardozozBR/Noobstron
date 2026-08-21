<?php

namespace App\Contracts;

use App\Support\AutomationAction;
use App\Support\AutomationActionResult;

interface AutomationActionHandler
{
    public function handle(
        AutomationAction $action,
        array $context
    ): AutomationActionResult;
}