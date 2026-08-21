<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'email' => 'El campo :attribute debe contener una dirección de correo válida.',
    'unique' => 'El valor de :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'date' => 'El campo :attribute debe contener una fecha válida.',
    'array' => 'El campo :attribute debe ser una lista válida.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',

    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],

    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
    ],

    'auth' => [
        'invalid_credentials' => 'El correo electrónico o la contraseña no son válidos.',
    ],

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'role' => 'rol',
        'action' => 'acción',
        'search' => 'búsqueda',
        'user_id' => 'usuario',
        'origin' => 'origen',
        'date_from' => 'fecha inicial',
        'date_to' => 'fecha final',
        'permissions' => 'permisos',
    ],
];