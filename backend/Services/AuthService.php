<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Helpers\Security;
use App\Helpers\SessionManager;
use App\Helpers\Logger;

class AuthService
{
    private User $userModel;
    private ActivityLog $activityLog;

    public function __construct(User $userModel, ActivityLog $activityLog)
    {
        $this->userModel = $userModel;
        $this->activityLog = $activityLog;
    }

    public function login(string $username, string $password): array
    {
        // Find user by username or email
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            $user = $this->userModel->findByEmail($username);
        }

        if (!$user) {
            Logger::warning('Login attempt: user not found', ['username' => $username]);
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        // Check account status
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'error' => 'Account is inactive. Contact administrator.'];
        }

        if ($user['status'] === 'locked') {
            return ['success' => false, 'error' => 'Account is locked. Contact administrator.'];
        }

        // Check if temporarily locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'error' => "Account temporarily locked. Try again in {$remaining} minutes."];
        }

        // Verify password
        if (!Security::verifyPassword($password, $user['password'])) {
            $this->userModel->incrementLoginAttempts($user['id']);
            
            // Lock after 5 failed attempts
            if (($user['login_attempts'] + 1) >= 5) {
                $this->userModel->lockAccount($user['id'], 30);
                Logger::warning('Account locked due to failed attempts', ['user_id' => $user['id']]);
                return ['success' => false, 'error' => 'Too many failed attempts. Account locked for 30 minutes.'];
            }

            Logger::warning('Failed login attempt', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Set session
        SessionManager::regenerate();
        SessionManager::setUser($user);

        // Generate CSRF token
        $csrfToken = SessionManager::generateCsrfToken();

        // Log activity
        $this->activityLog->log(
            $user['id'],
            'login',
            'user',
            $user['id'],
            'User logged in',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        Logger::info('User logged in', ['user_id' => $user['id'], 'username' => $username]);

        return [
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'avatar' => $user['avatar'],
                'csrf_token' => $csrfToken,
            ]
        ];
    }

    public function logout(int $userId): void
    {
        $this->activityLog->log(
            $userId,
            'logout',
            'user',
            $userId,
            'User logged out',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        SessionManager::destroy();
    }

    public function getCurrentUser(int $userId): ?array
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return null;
        }
        unset($user['password']);
        return $user;
    }
}
