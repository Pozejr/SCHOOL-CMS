<?php

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'username', 'email', 'password', 'role', 'status',
        'first_name', 'last_name', 'avatar', 'last_login',
        'login_attempts', 'locked_until'
    ];
    protected array $hidden = ['password'];

    public function findByUsername(string $username): ?array
    {
        return $this->findByField('username', $username);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findByField('email', $email);
    }

    public function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    public function incrementLoginAttempts(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET login_attempts = login_attempts + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    public function lockAccount(int $userId, int $minutes = 30): void
    {
        $sql = "UPDATE {$this->table} SET locked_until = NOW() + INTERVAL '{$minutes} minutes' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    public function isLocked(int $userId): bool
    {
        $user = $this->findById($userId);
        if (!$user) return false;
        if ($user['locked_until'] === null) return false;
        return strtotime($user['locked_until']) > time();
    }

    public function findAllExcept(int $excludeId, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id, username, email, role, status, first_name, last_name, avatar, last_login, created_at, updated_at 
                FROM {$this->table} WHERE id != :id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $excludeId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findActive(): array
    {
        $sql = "SELECT id, username, email, role, status, first_name, last_name FROM {$this->table} WHERE status = 'active' ORDER BY first_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
