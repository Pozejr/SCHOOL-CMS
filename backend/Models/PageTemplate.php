<?php

namespace App\Models;

class PageTemplate extends BaseModel
{
    protected string $table = 'page_templates';
    protected array $fillable = [
        'name', 'slug', 'description', 'sections'
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->findByField('slug', $slug);
    }

    public function findAllTemplates(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
