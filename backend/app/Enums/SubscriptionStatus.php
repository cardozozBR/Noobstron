<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isTerminal(): bool
    {
        return in_array(
            $this,
            [
                self::CANCELLED,
                self::EXPIRED,
            ],
            true
        );
    }
}