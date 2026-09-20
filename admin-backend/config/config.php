<?php
declare(strict_types=1);

return [
    'app_env' => getenv('APP_ENV') ?: 'local',
    'app_key' => getenv('APP_KEY') ?: 'change-me',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'wifi_manager',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
