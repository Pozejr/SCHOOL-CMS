<?php

namespace App\Models;

class File extends BaseModel
{
    protected string $table = 'files';
    protected array $fillable = [
        'filename', 'original_name', 'file_path', 'file_type',
        'mime_type', 'file_size', 'alt_text', 'entity_type',
        'entity_id', 'uploaded_by'
    ];

    public function findAllPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT f.*, u.first_name as uploader_first_name, u.last_name as uploader_last_name
                FROM {$this->table} f
                LEFT JOIN users u ON f.uploaded_by = u.id
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByEntity(string $entityType, int $entityId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE entity_type = :type AND entity_id = :id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['type' => $entityType, 'id' => $entityId]);
        return $stmt->fetchAll();
    }

    public function countByUser(int $userId, int $hours = 1): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE uploaded_by = :user_id AND created_at >= NOW() - INTERVAL '{$hours} hours'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetch()['total'];
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetch()['total'];
    }
}
