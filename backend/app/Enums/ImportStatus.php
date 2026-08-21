<?php

namespace App\Enums;

enum ImportStatus: string
{
    case UPLOADED = 'uploaded';
    case PARSED = 'parsed';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case COMPLETED_WITH_ERRORS = 'completed_with_errors';
    case FAILED = 'failed';

    public function isFinished(): bool
    {
        return in_array(
            $this,
            [
                self::COMPLETED,
                self::COMPLETED_WITH_ERRORS,
                self::FAILED,
            ],
            true
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::UPLOADED => 'Uploaded',
            self::PARSED => 'Parsed',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::COMPLETED_WITH_ERRORS =>
                'Completed with errors',
            self::FAILED => 'Failed',
        };
    }
}
