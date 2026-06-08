<?php

namespace App\Middleware;

use App\Helpers\Response;
use App\Helpers\SessionManager;

class RateLimitMiddleware
{
    private array $rateLimits = [];

    public function setLimit(string $key, int $max, int $window): void
    {
        $this->rateLimits[$key] = ['max' => $max, 'window' => $window];
    }

    public function check(string $key): bool
    {
        if (!isset($this->rateLimits[$key])) {
            return true;
        }

        $limit = $this->rateLimits[$key];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $storageKey = "rate_limit_{$key}_{$ip}";
        
        $attempts = SessionManager::get($storageKey, ['count' => 0, 'start' => time()]);
        
        $now = time();
        if (($now - $attempts['start']) > $limit['window']) {
            $attempts = ['count' => 0, 'start' => $now];
        }
        
        $attempts['count']++;
        SessionManager::set($storageKey, $attempts);

        if ($attempts['count'] > $limit['max']) {
            Response::tooManyRequests();
        }

        return true;
    }

    public function reset(string $key): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $storageKey = "rate_limit_{$key}_{$ip}";
        SessionManager::remove($storageKey);
    }
}
