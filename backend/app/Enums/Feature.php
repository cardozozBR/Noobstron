<?php

namespace App\Enums;

enum Feature: string
{
    case USERS = 'users';
    case AUDIT = 'audit';
    case BRANDING = 'branding';
    case LEADS = 'leads';
    case CUSTOMERS = 'customers';
    case IMPORTS = 'imports';
    case PIPELINES = 'pipelines';
    case OPPORTUNITIES = 'opportunities';
    case ACTIVITIES = 'activities';
    case CATALOG = 'catalog';
    case PROPOSALS = 'proposals';
    case SALES = 'sales';
    case RECEIVABLES = 'receivables';
    case CHARGES = 'charges';
    case FINANCIAL_INDICATORS = 'financial_indicators';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';
    case INBOX = 'inbox';
    case AI = 'ai';
    public function label(): string
    {
        return match ($this) {
            self::USERS => 'Usuários',
            self::AUDIT => 'Auditoria',
            self::BRANDING => 'Branding',
            self::LEADS => 'Leads',
            self::CUSTOMERS => 'Clientes',
            self::IMPORTS => 'Importações',
            self::PIPELINES => 'Pipelines',
            self::OPPORTUNITIES => 'Oportunidades',
            self::ACTIVITIES => 'Atividades',
            self::CATALOG => 'Catálogo',
            self::PROPOSALS => 'Propostas',
            self::SALES => 'Sales',
            self::RECEIVABLES => 'Contas a receber',
            self::CHARGES => 'Cobranças',
            self::FINANCIAL_INDICATORS => 'Indicadores financeiros',
            self::EMAIL => 'E-mail',
            self::WHATSAPP => 'WhatsApp',
            self::INBOX => 'Caixa de entrada',
            self::AI => 'Inteligência Artificial',
        };
    }
}
