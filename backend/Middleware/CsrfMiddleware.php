<?php

namespace App\Middleware;

use App\Helpers\Response;
use App\Helpers\SessionManager;

class CsrfMiddleware
{
    public function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only check CSRF for mutating requests
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }

        // Skip CSRF for login (no session yet)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/auth/login') || str_contains($uri, '/auth/csrf-token')) {
            return true;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            Response::error('CSRF_TOKEN_MISSING', 'CSRF token is required', 403);
        }

        if (!SessionManager::validateCsrfToken($token)) {
            Response::error('CSRF_TOKEN_INVALID', 'Invalid CSRF token', 403);
        }

        return true;
    }
}
