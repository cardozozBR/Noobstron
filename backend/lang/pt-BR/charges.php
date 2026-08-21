<?php

return [
    'navigation' => 'Cobranças',
    'title' => 'Cobranças',
    'description' => 'Acompanhe tentativas, envios e falhas de cobrança.',
    'create_title' => 'Nova cobrança',
    'create_description' => 'Crie uma nova tentativa de cobrança para um título pendente.',
    'empty' => 'Nenhuma cobrança registrada.',
    'create_action' => 'Criar cobrança',
    'back' => 'Voltar para cobranças',
    'select_receivable' => 'Selecione a conta a receber',
    'no_schedule' => 'Sem agendamento',
    'actions' => 'Ações',
    'sent_action' => 'Marcar enviada',
    'failed_action' => 'Marcar falha',
    'cancel_action' => 'Cancelar',
    'external_reference_placeholder' => 'Referência externa',
    'failure_reason_placeholder' => 'Motivo da falha',

    'fields' => [
        'receivable' => 'Conta a receber',
        'customer' => 'Cliente',
        'attempt' => 'Tentativa',
        'status' => 'Status',
        'scheduled_at' => 'Agendada para',
        'sent_at' => 'Enviada em',
        'channel' => 'Canal',
        'recipient' => 'Destinatário',
        'external_reference' => 'Referência externa',
        'failure_reason' => 'Motivo da falha',
    ],

    'statuses' => [
        'pending' => 'Pendente',
        'sent' => 'Enviada',
        'paid' => 'Paga',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelada',
    ],

    'messages' => [
        'created' => 'Cobrança criada.',
        'sent' => 'Cobrança marcada como enviada.',
        'failed' => 'Cobrança marcada como falha.',
        'cancelled' => 'Cobrança cancelada.',
    ],
];