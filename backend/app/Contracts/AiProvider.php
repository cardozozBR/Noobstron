<?php

namespace App\Contracts;

use App\Support\AiRequest;
use App\Support\AiResult;

interface AiProvider
{
    public function code(): string;

    public function execute(
        AiRequest $request
    ): AiResult;
}