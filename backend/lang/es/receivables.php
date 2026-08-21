<?php

return [
    'navigation' => 'Cuentas por cobrar',
    'title' => 'Cuentas por cobrar',
    'index_description' => 'Acompaña los títulos, vencimientos y pagos de los clientes.',
    'create_title' => 'Nueva cuenta por cobrar',
    'create_description' => 'Registra un nuevo título financiero para el cliente.',
    'edit_title' => 'Editar cuenta por cobrar',
    'edit_description' => 'Actualiza el título mientras esté pendiente.',
    'empty' => 'No hay cuentas por cobrar registradas.',
    'select_customer' => 'Selecciona el cliente',
    'no_sale' => 'Sin venta vinculada',
    'back' => 'Volver a cuentas por cobrar',
    'create_action' => 'Crear cuenta por cobrar',
    'edit_action' => 'Editar',
    'update_action' => 'Guardar cambios',
    'pay_action' => 'Marcar como pagada',
    'cancel_action' => 'Cancelar',
    'actions' => 'Acciones',
    'closed_edit_notice' => 'Solo los títulos pendientes pueden modificarse.',
    'payment_reference_placeholder' => 'Referencia del pago',

    'fields' => [
        'title' => 'Título',
        'customer' => 'Cliente',
        'sale' => 'Venta',
        'currency' => 'Moneda',
        'amount' => 'Valor',
        'amount_minor' => 'Valor en unidades menores',
        'due_date' => 'Vencimiento',
        'status' => 'Estado',
        'payment' => 'Pago',
        'paid_at' => 'Pagado en',
        'payment_reference' => 'Referencia',
    ],

    'statuses' => [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'cancelled' => 'Cancelado',
    ],

    'messages' => [
        'created' => 'Cuenta por cobrar creada.',
        'updated' => 'Cuenta por cobrar actualizada.',
        'paid' => 'Cuenta por cobrar marcada como pagada.',
        'cancelled' => 'Cuenta por cobrar cancelada.',
    ],
];