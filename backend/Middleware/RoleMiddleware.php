<?php

namespace App\Middleware;

use App\Helpers\Response;

class RoleMiddleware
{
    private const ROLE_HIERARCHY = [
        'super_admin' => 3,
        'admin' => 2,
        'editor' => 1,
    ];

    public function handle(array $user, array $allowedRoles): void
    {
        $userRole = $user['role'] ?? '';
        
        if (!in_array($userRole, $allowedRoles)) {
            Response::forbidden('You do not have permission to perform this action');
        }
    }

    public static function hasPermission(string $userRole, string $requiredRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$requiredRole] ?? 0;
        return $userLevel >= $requiredLevel;
    }

    public static function canEditAny(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin']);
    }

    public static function canDelete(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin']);
    }

    public static function canPublish(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin']);
    }

    public static function canManageUsers(string $role): bool
    {
        return $role === 'super_admin';
    }
}
