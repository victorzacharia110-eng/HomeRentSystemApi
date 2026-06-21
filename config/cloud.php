<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'FamilyBiz'),

    /*
    |--------------------------------------------------------------------------
    | Build Commands
    |--------------------------------------------------------------------------
    */
    'build' => [
        'commands' => [
            'composer install --no-dev --optimize-autoloader',
            'php artisan optimize',
            'npm install',
            'npm run build',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Server Configuration
    |--------------------------------------------------------------------------
    */
    'web' => [
        'commands' => [
            'php artisan serve --host=0.0.0.0 --port=8080',
        ],
        'routes' => [
            // CRITICAL: API routes MUST be handled by PHP
            [
                'path' => '/api/*',
                'handler' => 'php',
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ],
            // All other routes go to SPA
            [
                'path' => '/*',
                'handler' => 'spa',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SPA Configuration
    |--------------------------------------------------------------------------
    */
    'spa' => [
        'build' => [
            'commands' => [
                'npm install',
                'npm run build',
            ],
        ],
        'output' => 'dist', // or 'build' if using webpack
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Variables
    |--------------------------------------------------------------------------
    */
    'env' => [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://familybiz.online',
        'SPORTMONKS_TOKEN' => env('SPORTMONKS_TOKEN'),
        'SPORTMONKS_BASE_URL' => env('SPORTMONKS_BASE_URL', 'https://api.sportmonks.com/v3/football'),
    ],
];