<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\ActivityLog;
use App\Helpers\ContentParser;
use App\Helpers\Security;
use App\Helpers\Logger;

class PageSectionService
{
    private PageSection $sectionModel;
    private ActivityLog $activityLog;

    public function __construct(PageSection $sectionModel, ActivityLog $activityLog)
    {
        $this->sectionModel = $sectionModel;
        $this->activityLog = $activityLog;
    }

    public function getByPageId(int $pageId): array
    {
        $sections = $this->sectionModel->findByPageId($pageId);
        return array_map(fn($s) => $this->enrichSection($s), $sections);
    }

    public function getById(int $id): ?array
    {
        $section = $this->sectionModel->findById($id);
        if (!$section) return null;
        return $this->enrichSection($section);
    }

    public function create(int $pageId, array $data, int $userId): array
    {
        $sectionData = [
            'page_id' => $pageId,
            'section_type' => $data['section_type'] ?? 'text',
            'title' => Security::sanitizeString($data['title'] ?? ''),
            'content' => $data['content'] ?? '',
            'image_url' => $data['image_url'] ?? null,
            'image_caption' => Security::sanitizeString($data['image_caption'] ?? ''),
            'video_url' => $data['video_url'] ?? null,
            'gallery_urls' => isset($data['gallery_urls']) ? json_encode($data['gallery_urls']) : null,
            'pdf_url' => $data['pdf_url'] ?? null,
            'display_order' => (int)($data['display_order'] ?? 0),
            'layout' => $data['layout'] ?? 'full',
        ];

        $section = $this->sectionModel->create($sectionData);

        $this->activityLog->log(
            $userId, 'create', 'page_section', $section['id'],
            "Added section: {$section['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'section' => $this->enrichSection($section)];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $section = $this->sectionModel->findById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found'];
        }

        $updateData = [];
        $allowed = ['section_type', 'title', 'content', 'image_url', 'image_caption',
                     'video_url', 'gallery_urls', 'pdf_url', 'display_order', 'layout'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'gallery_urls' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } elseif (in_array($field, ['title', 'image_caption'])) {
                    $updateData[$field] = Security::sanitizeString($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        $updated = $this->sectionModel->update($id, $updateData);

        $this->activityLog->log(
            $userId, 'update', 'page_section', $id,
            "Updated section: " . ($updateData['title'] ?? $section['title']),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'section' => $this->enrichSection($updated)];
    }

    public function delete(int $id, int $userId): array
    {
        $section = $this->sectionModel->findById($id);
        if (!$section) {
            return ['success' => false, 'error' => 'Section not found'];
        }

        $this->sectionModel->delete($id);

        $this->activityLog->log(
            $userId, 'delete', 'page_section', $id,
            "Deleted section: {$section['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'Section deleted'];
    }

    public function reorder(int $pageId, array $sectionIds, int $userId): array
    {
        $this->sectionModel->reorder($pageId, $sectionIds);

        $this->activityLog->log(
            $userId, 'reorder', 'page_section', $pageId,
            "Reordered sections on page",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'Sections reordered'];
    }

    /**
     * Parse section content to HTML and add computed fields.
     */
    private function enrichSection(array $section): array
    {
        $section['content_html'] = ContentParser::parse($section['content'] ?? '');
        if ($section['gallery_urls'] && is_string($section['gallery_urls'])) {
            $section['gallery_urls'] = json_decode($section['gallery_urls'], true) ?? [];
        }
        if ($section['gallery_urls'] === null) {
            $section['gallery_urls'] = [];
        }
        return $section;
    }
}
