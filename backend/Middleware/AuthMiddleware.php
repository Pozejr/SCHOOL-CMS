<?php

namespace App\Middleware;

use App\Helpers\Response;
use App\Helpers\SessionManager;

class AuthMiddleware
{
    private int $sessionLifetime;

    public function __construct(int $sessionLifetime = 7200)
    {
        $this->sessionLifetime = $sessionLifetime;
    }

    public function handle(): ?array
    {
        if (!SessionManager::isAuthenticated()) {
            Response::unauthorized('Please log in to access this resource');
        }

        if (SessionManager::isExpired($this->sessionLifetime)) {
            SessionManager::destroy();
            Response::unauthorized('Session expired. Please log in again');
        }

        // Update login time to extend session
        $_SESSION['login_time'] = time();

        return SessionManager::getUser();
    }
}
