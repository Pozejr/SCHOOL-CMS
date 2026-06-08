<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Middleware\RoleMiddleware;

class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canManageUsers($user['role'])) {
            Response::forbidden('Only Super Admin can manage users');
        }

        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);

        $result = $this->userService->getAll($user['id'], $page, $perPage);
        Response::paginated($result['users'], $page, $perPage, $result['total']);
    }

    public function store(): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canManageUsers($user['role'])) {
            Response::forbidden('Only Super Admin can create users');
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->userService->create($data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('CREATE_FAILED', $result['error'] ?? 'Failed to create user');
        }

        Response::success($result['user'], 'User created successfully', 201);
    }

    public function update(array $params): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canManageUsers($user['role'])) {
            Response::forbidden('Only Super Admin can update users');
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->userService->update((int)$params['id'], $data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('UPDATE_FAILED', $result['error'] ?? 'Failed to update user');
        }

        Response::success($result['user'], 'User updated successfully');
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canManageUsers($user['role'])) {
            Response::forbidden('Only Super Admin can delete users');
        }

        $result = $this->userService->delete((int)$params['id'], $user['id']);

        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }
}
