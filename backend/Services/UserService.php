<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Helpers\Validator;

class UserService
{
    private User $userModel;
    private ActivityLog $activityLog;

    public function __construct(User $userModel, ActivityLog $activityLog)
    {
        $this->userModel = $userModel;
        $this->activityLog = $activityLog;
    }

    public function getAll(int $excludeId, int $page = 1, int $perPage = 15): array
    {
        $users = $this->userModel->findAllExcept($excludeId, $page, $perPage);
        $total = $this->userModel->count() - 1;
        return ['users' => $users, 'total' => $total];
    }

    public function create(array $data, int $createdBy): array
    {
        $validator = new Validator($data, [
            'username' => 'required|min:3|max:100|alpha_num',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8',
            'role' => 'required|in:super_admin,admin,editor',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
        ]);

        if (!$validator->validate()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        // Check uniqueness
        if ($this->userModel->findByUsername($data['username'])) {
            return ['success' => false, 'errors' => [['field' => 'username', 'message' => 'Username already exists']]];
        }
        if ($this->userModel->findByEmail($data['email'])) {
            return ['success' => false, 'errors' => [['field' => 'email', 'message' => 'Email already exists']]];
        }

        $userData = [
            'username' => Security::sanitizeString($data['username']),
            'email' => Security::sanitizeString($data['email']),
            'password' => Security::hashPassword($data['password']),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'first_name' => Security::sanitizeString($data['first_name']),
            'last_name' => Security::sanitizeString($data['last_name']),
        ];

        $user = $this->userModel->create($userData);
        unset($user['password']);

        $this->activityLog->log(
            $createdBy,
            'create',
            'user',
            $user['id'],
            "Created user: {$user['username']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'user' => $user];
    }

    public function update(int $id, array $data, int $updatedBy): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $updateData = [];

        if (isset($data['email'])) {
            $existingEmail = $this->userModel->findByEmail($data['email']);
            if ($existingEmail && $existingEmail['id'] !== $id) {
                return ['success' => false, 'errors' => [['field' => 'email', 'message' => 'Email already in use']]];
            }
            $updateData['email'] = Security::sanitizeString($data['email']);
        }
        if (isset($data['role'])) {
            $validator = new Validator($data, ['role' => 'in:super_admin,admin,editor']);
            if (!$validator->validate()) {
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            $updateData['role'] = $data['role'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['first_name'])) {
            $updateData['first_name'] = Security::sanitizeString($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $updateData['last_name'] = Security::sanitizeString($data['last_name']);
        }
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'errors' => [['field' => 'password', 'message' => 'Password must be at least 8 characters']]];
            }
            $updateData['password'] = Security::hashPassword($data['password']);
        }

        $updated = $this->userModel->update($id, $updateData);
        if ($updated) {
            unset($updated['password']);
        }

        $this->activityLog->log(
            $updatedBy,
            'update',
            'user',
            $id,
            "Updated user: " . ($user['username'] ?? ''),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'user' => $updated];
    }

    public function delete(int $id, int $deletedBy): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        if ($id === $deletedBy) {
            return ['success' => false, 'error' => 'Cannot delete your own account'];
        }

        $this->userModel->delete($id);

        $this->activityLog->log(
            $deletedBy,
            'delete',
            'user',
            $id,
            "Deleted user: {$user['username']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'User deleted successfully'];
    }
}
