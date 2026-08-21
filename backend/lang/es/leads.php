<?php

return [
    'title' => 'Leads',
    'new' => 'Nuevo lead',
    'edit' => 'Editar lead',
    'empty' => 'No se encontraron leads.',

    'fields' => [
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'status' => 'Estado',
        'source' => 'Origen',
        'responsible' => 'Responsable',
        'tags' => 'Etiquetas',
        'notes' => 'Observaciones',
    ],

    'filters' => [
        'search' => 'Buscar',
        'search_placeholder' => 'Nombre, correo o teléfono',
        'all_statuses' => 'Todos',
        'all_sources' => 'Todos',
        'all_responsibles' => 'Todos',
        'filter' => 'Filtrar',
        'clear' => 'Limpiar',
    ],

    'actions' => [
        'create' => 'Crear lead',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'save' => 'Guardar cambios',
        'back' => 'Volver',
        'cancel' => 'Cancelar',
    ],

    'responsible_none' => 'Sin responsable',
    'tag_placeholder' => 'Etiqueta',
    'actions_column' => 'Acciones',
    'contact' => 'Contacto',

    'status' => [
        'new' => 'Nuevo',
        'contacted' => 'Contactado',
        'qualified' => 'Calificado',
        'unqualified' => 'No calificado',
    ],

    'source' => [
        'manual' => 'Manual',
        'website' => 'Sitio web',
        'referral' => 'Referido',
        'social' => 'Red social',
        'other' => 'Otro',
    ],

    'messages' => [
        'created' => 'Lead creado correctamente.',
        'updated' => 'Lead actualizado correctamente.',
        'deleted' => 'Lead eliminado correctamente.',
    ],
    'conversion' => 'Conversión',
    'convert' => 'Convertir en cliente',
    'customer_type' => 'Tipo de cliente',
    'converted' => 'Lead convertido',
    'individual' => 'Persona física',
    'convert_confirm' => '¿Desea convertir este lead en cliente?',
    'view_customer' => 'Ver cliente',
    'company' => 'Persona jurídica',
    'converted_at' => 'Convertido el',
    'convert_help' => 'Cree un cliente a partir de los datos de este lead.',
    'conversion_success' => 'Lead convertido en cliente correctamente.',

    'ai_rewrite' => 'Reescribir con IA',
    'ai_rewrite_empty' => 'Escribe las observaciones antes de usar la IA.',
    'ai_rewrite_loading' => 'Reescribiendo...',
    'ai_rewrite_success' => 'Observaciones reescritas.',
    'ai_rewrite_error' => 'No fue posible reescribir las observaciones.',
    'ai_rewrite_instruction' => 'Reescribe estas observaciones del lead con claridad, naturalidad y tono profesional, preservando el significado original.',];
