<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'email' => 'The :attribute field must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'in' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute field must be an integer.',
    'date' => 'The :attribute field must be a valid date.',
    'array' => 'The :attribute field must be an array.',
    'exists' => 'The selected :attribute is invalid.',

    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
        'numeric' => 'The :attribute field must be at least :min.',
        'array' => 'The :attribute field must have at least :min items.',
    ],

    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'array' => 'The :attribute field must not have more than :max items.',
    ],

    'auth' => [
        'invalid_credentials' => 'The email or password is invalid.',
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'role' => 'role',
        'action' => 'action',
        'search' => 'search',
        'user_id' => 'user',
        'origin' => 'origin',
        'date_from' => 'start date',
        'date_to' => 'end date',
        'permissions' => 'permissions',
    ],
];