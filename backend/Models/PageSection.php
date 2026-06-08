<?php

namespace App\Models;

class PageSection extends BaseModel
{
    protected string $table = 'page_sections';
    protected array $fillable = [
        'page_id', 'section_type', 'title', 'content',
        'image_url', 'image_caption', 'video_url',
        'gallery_urls', 'pdf_url', 'display_order', 'layout'
    ];

    public function findByPageId(int $pageId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE page_id = :page_id 
                ORDER BY display_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['page_id' => $pageId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        return parent::findById($id);
    }

    public function deleteByPageId(int $pageId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE page_id = :page_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['page_id' => $pageId]);
    }

    public function reorder(int $pageId, array $sectionIds): void
    {
        $sql = "UPDATE {$this->table} SET display_order = :order WHERE id = :id AND page_id = :page_id";
        $stmt = $this->pdo->prepare($sql);
        foreach ($sectionIds as $order => $id) {
            $stmt->execute([
                'order' => $order,
                'id' => $id,
                'page_id' => $pageId,
            ]);
        }
    }
}
