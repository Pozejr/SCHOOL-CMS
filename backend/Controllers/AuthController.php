<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Helpers\Logger;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            Response::validationError([
                ['field' => !empty($username) ? 'password' : 'username', 'message' => 'This field is required']
            ]);
        }

        $result = $this->authService->login($username, $password);

        if (!$result['success']) {
            Response::error('AUTH_FAILED', $result['error'], 401);
        }

        Response::success($result['data'], 'Login successful');
    }

    public function logout(): void
    {
        $user = SessionManager::getUser();
        if ($user) {
            $this->authService->logout($user['id']);
        }
        Response::success(null, 'Logged out successfully');
    }

    public function me(): void
    {
        $user = SessionManager::getUser();
        if (!$user) {
            Response::unauthorized();
        }

        $currentUser = $this->authService->getCurrentUser($user['id']);
        if (!$currentUser) {
            Response::unauthorized();
        }

        $currentUser['csrf_token'] = SessionManager::get('csrf_token');
        Response::success($currentUser);
    }

    public function csrfToken(): void
    {
        SessionManager::start();
        $token = SessionManager::generateCsrfToken();
        Response::success(['csrf_token' => $token]);
    }
}
