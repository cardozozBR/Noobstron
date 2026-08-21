<?php

return [
    'navigation' => 'Actividades',
    'title' => 'Actividades',
    'new' => 'Nueva actividad',
    'edit' => 'Editar actividad',
    'create_title' => 'Nueva actividad',
    'edit_title' => 'Editar actividad',
    'create_description' => 'Cree una tarea, llamada, reunión o seguimiento.',
    'index_description' => 'Gestione tareas, contactos y compromisos comerciales.',
    'empty' => 'No se encontraron actividades.',
    'none' => 'Ninguno',
    'actions_column' => 'Acciones',
    'confirm_delete' => '¿Está seguro de que desea eliminar esta actividad?',

    'types' => [
        'task' => 'Tarea',
        'call' => 'Llamada',
        'meeting' => 'Reunión',
        'follow_up' => 'Seguimiento',
    ],

    'statuses' => [
        'pending' => 'Pendiente',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ],

    'fields' => [
        'type' => 'Tipo',
        'status' => 'Estado',
        'title' => 'Título',
        'description' => 'Descripción',
        'customer' => 'Cliente',
        'opportunity' => 'Oportunidad',
        'responsible' => 'Responsable',
        'due_at' => 'Vencimiento',
    ],

    'filters' => [
        'search' => 'Buscar',
        'search_placeholder' => 'Título de la actividad',
        'all_types' => 'Todos los tipos',
        'all_statuses' => 'Todos los estados',
        'all_customers' => 'Todos los clientes',
        'all_opportunities' => 'Todas las oportunidades',
        'all_responsibles' => 'Todos los responsables',
        'filter' => 'Filtrar',
        'clear' => 'Limpiar',
    ],

    'actions' => [
        'create' => 'Crear actividad',
        'save' => 'Guardar cambios',
        'back' => 'Volver',
        'cancel' => 'Cancelar',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'complete' => 'Completar',
        'reopen' => 'Reabrir',
        'cancel_activity' => 'Cancelar actividad',
    ],

    'messages' => [
        'created' => 'Actividad creada correctamente.',
        'updated' => 'Actividad actualizada correctamente.',
        'completed' => 'Actividad completada correctamente.',
        'reopened' => 'Actividad reabierta correctamente.',
        'cancelled' => 'Actividad cancelada correctamente.',
        'deleted' => 'Actividad eliminada correctamente.',
    ],

    'ai_rewrite' => 'Reescribir con IA',
    'ai_rewrite_empty' => 'Escribe la descripcion antes de usar la IA.',
    'ai_rewrite_loading' => 'Reescribiendo...',
    'ai_rewrite_success' => 'Descripcion reescrita.',
    'ai_rewrite_error' => 'No fue posible reescribir la descripcion.',
    'ai_rewrite_instruction' => 'Reescribe esta descripcion de la actividad con claridad, naturalidad y tono profesional, preservando el significado original.',];
