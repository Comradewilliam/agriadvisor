<?php
return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'dbname'   => getenv('DB_NAME') ?: 'agridb',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'apis' => [
        'africas_talking' => [
            'username' => getenv('AT_USERNAME') ?: 'sandbox',
            'api_key'  => getenv('AT_API_KEY'),
        ],
        'openrouter' => [
            'api_key' => getenv('OPENROUTER_API_KEY') ?: getenv('OPENAI_API_KEY'),
            'model'   => getenv('OPENROUTER_MODEL') ?: 'google/gemma-4-26b-a4b-it:free',
            'fallback_model' => getenv('OPENROUTER_FALLBACK_MODEL') ?: '',
        ],
        'weatherapi' => [
            'api_key' => getenv('WEATHER_API_KEY'),
        ],
    ],
];
