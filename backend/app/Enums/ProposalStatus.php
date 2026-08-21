<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::SENT => 'Enviada',
            self::ACCEPTED => 'Aceita',
            self::REJECTED => 'Recusada',
            self::EXPIRED => 'Expirada',
        };
    }
}
