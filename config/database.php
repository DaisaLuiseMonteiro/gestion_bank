<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('PG_DATABASE_URL', null),
            'host' => env('DB_HOST', 'turntable.proxy.rlwy.net'),
            'port' => env('DB_PORT', '30579'),
            'database' => env('DB_DATABASE', 'railway'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'oajQBMSryQOaJKtYlZTMlMZuAjTYVgfq'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'require'),
        ],

        'neon' => [
            'driver' => 'pgsql',
            'host' => env('NEON_DB_HOST', 'ep-withered-fire-ah109dcp.c-3.us-east-1.aws.neon.tech'),
            'port' => env('NEON_DB_PORT', '5432'),
            'database' => env('NEON_DB_DATABASE', 'neondb'),
            'username' => env('NEON_DB_USERNAME', 'neondb_owner'),
            'password' => env('NEON_DB_PASSWORD', 'npg_aGkwY6fq5SXh'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('NEON_SSLMODE', 'require'),
            'options' => [
                'sslmode' => env('NEON_SSLMODE', 'require'),
                'options' => '--client_encoding=utf8',
            ],
        ],
    ],

    'migrations' => 'migrations',

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];