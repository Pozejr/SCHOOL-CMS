<?php

namespace App\Models;

use PDO;
use PDOStatement;

abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAll(int $page = 1, int $perPage = 15, string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'];
    }

    public function create(array $data): array
    {
        $filteredData = $this->filterFillable($data);
        $columns = implode(', ', array_map(fn($col) => $col, array_keys($filteredData)));
        $placeholders = implode(', ', array_map(fn($col) => ":{$col}", array_keys($filteredData)));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders}) RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filteredData);
        
        return $stmt->fetch();
    }

    public function update(int $id, array $data): ?array
    {
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return $this->findById($id);
        }

        $setClauses = [];
        foreach (array_keys($filteredData) as $column) {
            $setClauses[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClauses);
        
        $filteredData['id'] = $id;
        $sql = "UPDATE {$this->table} SET {$setClause}, updated_at = NOW() WHERE {$this->primaryKey} = :id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filteredData);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function softDelete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW(), updated_at = NOW() WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function findByField(string $field, mixed $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['value' => $value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllByField(string $field, mixed $value, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    protected function filterFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
