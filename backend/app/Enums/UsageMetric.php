<?php

namespace App\Enums;

enum UsageMetric: string
{
    case USERS = 'users';
    case STORAGE_BYTES = 'storage_bytes';
    case MESSAGES = 'messages';
    case AI_TOKENS = 'ai_tokens';

    public function label(): string
    {
        return match ($this) {
            self::USERS => 'Usuários',
            self::STORAGE_BYTES => 'Storage',
            self::MESSAGES => 'Mensagens',
            self::AI_TOKENS => 'IA (tokens)',
        };
    }
}