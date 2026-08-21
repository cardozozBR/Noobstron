<?php

return [
    'navigation' => 'Cobros',
    'title' => 'Cobros',
    'description' => 'Acompaña intentos, envíos y fallos de cobro.',
    'create_title' => 'Nuevo cobro',
    'create_description' => 'Crea un nuevo intento de cobro para una cuenta pendiente.',
    'empty' => 'No hay cobros registrados.',
    'create_action' => 'Crear cobro',
    'back' => 'Volver a cobros',
    'select_receivable' => 'Selecciona la cuenta por cobrar',
    'no_schedule' => 'Sin programación',
    'actions' => 'Acciones',
    'sent_action' => 'Marcar enviado',
    'failed_action' => 'Marcar fallo',
    'cancel_action' => 'Cancelar',
    'external_reference_placeholder' => 'Referencia externa',
    'failure_reason_placeholder' => 'Motivo del fallo',

    'fields' => [
        'receivable' => 'Cuenta por cobrar',
        'customer' => 'Cliente',
        'attempt' => 'Intento',
        'status' => 'Estado',
        'scheduled_at' => 'Programado para',
        'sent_at' => 'Enviado en',
        'channel' => 'Canal',
        'recipient' => 'Destinatario',
        'external_reference' => 'Referencia externa',
        'failure_reason' => 'Motivo del fallo',
    ],

    'statuses' => [
        'pending' => 'Pendiente',
        'sent' => 'Enviado',
        'paid' => 'Pagado',
        'failed' => 'Falló',
        'cancelled' => 'Cancelado',
    ],

    'messages' => [
        'created' => 'Cobro creado.',
        'sent' => 'Cobro marcado como enviado.',
        'failed' => 'Cobro marcado como fallido.',
        'cancelled' => 'Cobro cancelado.',
    ],
];