<?php

namespace App\Enums;

enum ChargeStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::SENT => 'Enviada',
            self::PAID => 'Paga',
            self::FAILED => 'Falhou',
            self::CANCELLED => 'Cancelada',
        };
    }
}