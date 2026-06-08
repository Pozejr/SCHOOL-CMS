<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'School CMS',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'key' => $_ENV['APP_KEY'] ?? '',
    'timezone' => 'Africa/Nairobi',
    'version' => '1.0.0',
];
