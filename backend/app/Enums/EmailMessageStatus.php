<?php

namespace App\Enums;

enum EmailMessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando envio',
            self::SENT => 'Enviado',
            self::FAILED => 'Não foi possível enviar',
        };
    }
}
