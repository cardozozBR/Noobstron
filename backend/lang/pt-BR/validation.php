<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'O campo :attribute deve conter um endereço de e-mail válido.',
    'unique' => 'O valor informado para :attribute já está em uso.',
    'confirmed' => 'A confirmação do campo :attribute não corresponde.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'date' => 'O campo :attribute deve conter uma data válida.',
    'array' => 'O campo :attribute deve ser uma lista válida.',
    'exists' => 'O valor selecionado para :attribute é inválido.',

    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
    ],

    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
    ],

    'auth' => [
        'invalid_credentials' => 'E-mail ou senha inválidos.',
    ],

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'role' => 'papel',
        'action' => 'ação',
        'search' => 'busca',
        'user_id' => 'usuário',
        'origin' => 'origem',
        'date_from' => 'data inicial',
        'date_to' => 'data final',
        'permissions' => 'permissões',
    ],
];