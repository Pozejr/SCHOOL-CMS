<?php

return [
    'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173'),
    'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,PATCH'),
    'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,X-CSRF-Token,Authorization'),
    'allow_credentials' => true,
    'max_age' => 86400,
];
