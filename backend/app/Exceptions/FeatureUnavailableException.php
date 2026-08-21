<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class FeatureUnavailableException extends HttpException
{
    public function __construct(
        public readonly string $feature,
    ) {
        parent::__construct(
            403,
            'Feature unavailable for this tenant.'
        );
    }
}