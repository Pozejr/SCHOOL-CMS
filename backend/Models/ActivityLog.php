<?php

namespace App\Models;

class ActivityLog extends BaseModel
{
    protected string $table = 'activity_logs';
    protected array $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'description', 'ip_address', 'user_agent'
    ];

    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        string $description = '',
        ?string $ipAddress = null,
        string $userAgent = ''
    ): array {
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function findRecent(int $limit = 10): array
    {
        $sql = "SELECT al.*, u.first_name, u.last_name, u.username
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
