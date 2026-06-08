<?php

namespace App\Models;

class Event extends BaseModel
{
    protected string $table = 'events';
    protected array $fillable = [
        'title', 'slug', 'description', 'venue', 'event_date',
        'end_date', 'featured_image', 'status', 'created_by', 'updated_by', 'deleted_at'
    ];

    public function findPublished(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, u.first_name as creator_first_name, u.last_name as creator_last_name
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.status = 'published' AND e.deleted_at IS NULL
                ORDER BY e.event_date ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findUpcoming(int $limit = 5): array
    {
        $sql = "SELECT e.*, u.first_name as creator_first_name, u.last_name as creator_last_name
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.status = 'published' AND e.deleted_at IS NULL AND e.event_date >= NOW()
                ORDER BY e.event_date ASC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllWithCreator(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, u.first_name as creator_first_name, u.last_name as creator_last_name
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.deleted_at IS NULL
                ORDER BY e.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT e.*, u.first_name as creator_first_name, u.last_name as creator_last_name
                FROM {$this->table} e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.slug = :slug AND e.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE slug = :slug AND deleted_at IS NULL";
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'] > 0;
    }

    public function countPublished(): int
    {
        return $this->count(['status' => 'published']);
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetch()['total'];
    }
}
