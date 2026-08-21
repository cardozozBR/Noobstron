<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI provider
    |--------------------------------------------------------------------------
    |
    | External AI remains disabled unless explicitly enabled through
    | environment configuration.
    |
    */

    'default' => env(
        'AI_PROVIDER',
        null
    ),

    'providers' => [
        'openai' => [
            'enabled' => env(
                'OPENAI_ENABLED',
                false
            ),

            'model' => env(
                'OPENAI_MODEL',
                null
            ),

            /*
             * Secrets remain in environment / secret storage.
             * They are never persisted in tenant tables.
             */
            'api_key' => env(
                'OPENAI_API_KEY',
                null
            ),

            'base_url' => env(
                'OPENAI_BASE_URL',
                'https://api.openai.com/v1'
            ),

            'timeout' => (int) env(
                'OPENAI_TIMEOUT',
                30
            ),

            'parameters' => [],
        ],
    ],
];