<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\ActivityLog;
use App\Helpers\SlugGenerator;
use App\Helpers\Security;
use App\Helpers\SeoGenerator;
use App\Helpers\Logger;
use App\Helpers\Validator;

class PageService
{
    private Page $pageModel;
    private PageSection $sectionModel;
    private ActivityLog $activityLog;

    public function __construct(Page $pageModel, PageSection $sectionModel, ActivityLog $activityLog)
    {
        $this->pageModel = $pageModel;
        $this->sectionModel = $sectionModel;
        $this->activityLog = $activityLog;
    }

    public function getAll(int $page = 1, int $perPage = 15): array
    {
        $pages = $this->pageModel->findAllWithCreator($page, $perPage);
        $total = $this->pageModel->countAll();
        return ['pages' => $pages, 'total' => $total];
    }

    public function getPublished(int $page = 1, int $perPage = 15): array
    {
        $pages = $this->pageModel->findPublished($page, $perPage);
        $total = $this->pageModel->countPublished();
        return ['pages' => $pages, 'total' => $total];
    }

    public function getById(int $id): ?array
    {
        $page = $this->pageModel->findById($id);
        if (!$page || $page['deleted_at']) return null;
        return $page;
    }

    /**
     * Get a page with its raw sections for editing.
     *
     * Returns structured data only — no generated HTML, no SEO.
     * The content field is plain text as the admin typed it.
     * This is the data source for the Edit Page screen.
     */
    public function getByIdWithSections(int $id): ?array
    {
        $page = $this->pageModel->findById($id);
        if (!$page || $page['deleted_at']) return null;

        $sections = $this->sectionModel->findByPageId($id);

        // Decode gallery_urls from JSON string to array for the frontend
        $sections = array_map(function ($s) {
            if (isset($s['gallery_urls']) && is_string($s['gallery_urls'])) {
                $s['gallery_urls'] = json_decode($s['gallery_urls'], true) ?? [];
            }
            if (!isset($s['gallery_urls']) || $s['gallery_urls'] === null) {
                $s['gallery_urls'] = [];
            }
            // Explicitly do NOT add content_html — that's output only
            return $s;
        }, $sections);

        $page['sections'] = $sections;
        return $page;
    }

    public function getBySlug(string $slug): ?array
    {
        $page = $this->pageModel->findBySlug($slug);
        if (!$page) return null;
        return $page;
    }

    /**
     * Get a full page with sections and SEO data (for public rendering).
     */
    public function getFullPage(string $slug): ?array
    {
        $page = $this->pageModel->findBySlug($slug);
        if (!$page) return null;

        $sections = $this->sectionModel->findByPageId($page['id']);

        // Enrich sections with parsed HTML for public output
        $enrichedSections = array_map(function ($s) {
            $s['content_html'] = \App\Helpers\ContentParser::parse($s['content'] ?? '');
            if ($s['gallery_urls'] && is_string($s['gallery_urls'])) {
                $s['gallery_urls'] = json_decode($s['gallery_urls'], true) ?? [];
            }
            if ($s['gallery_urls'] === null) {
                $s['gallery_urls'] = [];
            }
            return $s;
        }, $sections);

        // Generate SEO data
        $seo = SeoGenerator::generate($page, $enrichedSections);

        $page['sections'] = $enrichedSections;
        $page['seo'] = $seo;

        return $page;
    }

    public function create(array $data, int $userId): array
    {
        $validator = new Validator($data, [
            'title' => 'required|max:255',
            'status' => 'in:published,draft',
        ]);

        if (!$validator->validate()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $slug = SlugGenerator::generate($data['title'], fn($s) => $this->pageModel->slugExists($s));

        $pageData = [
            'title' => Security::sanitizeString($data['title']),
            'slug' => $slug,
            'content' => '',
            'excerpt' => Security::sanitizeString($data['excerpt'] ?? ''),
            'status' => $data['status'] ?? 'draft',
            'meta_title' => Security::sanitizeString($data['meta_title'] ?? ''),
            'meta_description' => Security::sanitizeString($data['meta_description'] ?? ''),
            'featured_image' => $data['featured_image'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'template' => $data['template'] ?? 'default',
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        $page = $this->pageModel->create($pageData);

        // Create sections if provided
        if (!empty($data['sections']) && is_array($data['sections'])) {
            foreach ($data['sections'] as $order => $sectionData) {
                $this->sectionModel->create([
                    'page_id' => $page['id'],
                    'section_type' => $sectionData['section_type'] ?? 'text',
                    'title' => Security::sanitizeString($sectionData['title'] ?? ''),
                    'content' => $sectionData['content'] ?? '',
                    'image_url' => $sectionData['image_url'] ?? null,
                    'image_caption' => Security::sanitizeString($sectionData['image_caption'] ?? ''),
                    'video_url' => $sectionData['video_url'] ?? null,
                    'gallery_urls' => isset($sectionData['gallery_urls']) ? json_encode($sectionData['gallery_urls']) : null,
                    'pdf_url' => $sectionData['pdf_url'] ?? null,
                    'display_order' => $order,
                    'layout' => $sectionData['layout'] ?? 'full',
                ]);
            }
        }

        $this->activityLog->log(
            $userId, 'create', 'page', $page['id'],
            "Created page: {$page['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        Logger::info('Page created', ['page_id' => $page['id'], 'title' => $page['title']]);

        return ['success' => true, 'page' => $page];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $page = $this->pageModel->findById($id);
        if (!$page || $page['deleted_at']) {
            return ['success' => false, 'error' => 'Page not found'];
        }

        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = Security::sanitizeString($data['title']);
        }
        if (array_key_exists('content', $data)) {
            $updateData['content'] = $data['content'];
        }
        if (isset($data['excerpt'])) {
            $updateData['excerpt'] = Security::sanitizeString($data['excerpt']);
        }
        if (isset($data['status'])) {
            $validator = new Validator($data, ['status' => 'in:published,draft']);
            if (!$validator->validate()) {
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            $updateData['status'] = $data['status'];
        }
        if (isset($data['meta_title'])) {
            $updateData['meta_title'] = Security::sanitizeString($data['meta_title']);
        }
        if (isset($data['meta_description'])) {
            $updateData['meta_description'] = Security::sanitizeString($data['meta_description']);
        }
        if (array_key_exists('featured_image', $data)) {
            $updateData['featured_image'] = $data['featured_image'];
        }
        if (isset($data['sort_order'])) {
            $updateData['sort_order'] = (int)$data['sort_order'];
        }
        if (isset($data['template'])) {
            $updateData['template'] = $data['template'];
        }
        if (isset($data['slug'])) {
            if ($this->pageModel->slugExists($data['slug'], $id)) {
                return ['success' => false, 'errors' => [['field' => 'slug', 'message' => 'Slug already exists']]];
            }
            $updateData['slug'] = Security::sanitizeString($data['slug']);
        }

        $updateData['updated_by'] = $userId;
        $updated = $this->pageModel->update($id, $updateData);

        // Sync sections if provided
        if (array_key_exists('sections', $data) && is_array($data['sections'])) {
            $this->syncSections($id, $data['sections'], $userId);
        }

        $this->activityLog->log(
            $userId, 'update', 'page', $id,
            "Updated page: " . ($updateData['title'] ?? $page['title']),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        // Return the fully updated page with sections
        $result = $this->getByIdWithSections($id);
        return ['success' => true, 'page' => $result];
    }

    public function delete(int $id, int $userId): array
    {
        $page = $this->pageModel->findById($id);
        if (!$page || $page['deleted_at']) {
            return ['success' => false, 'error' => 'Page not found'];
        }

        $this->pageModel->softDelete($id);

        $this->activityLog->log(
            $userId, 'delete', 'page', $id,
            "Deleted page: {$page['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'Page deleted successfully'];
    }

    /**
     * Sync sections for a page during update.
     *
     * - Sections with existing numeric IDs → update in place
     * - Sections with new_* or no ID → create new
     * - Existing sections not in the payload → delete
     */
    private function syncSections(int $pageId, array $sections, int $userId): void
    {
        $existingSections = $this->sectionModel->findByPageId($pageId);
        $existingIds = array_map(fn($s) => (int)$s['id'], $existingSections);
        $sentIds = [];

        foreach ($sections as $order => $sectionData) {
            $rawId = $sectionData['id'] ?? null;
            $isExisting = $rawId && is_numeric($rawId) && in_array((int)$rawId, $existingIds);

            $sectionPayload = [
                'section_type' => $sectionData['section_type'] ?? 'text',
                'title' => Security::sanitizeString($sectionData['title'] ?? ''),
                'content' => $sectionData['content'] ?? '',
                'image_url' => $sectionData['image_url'] ?? null,
                'image_caption' => Security::sanitizeString($sectionData['image_caption'] ?? ''),
                'video_url' => $sectionData['video_url'] ?? null,
                'gallery_urls' => isset($sectionData['gallery_urls']) ? json_encode($sectionData['gallery_urls']) : null,
                'pdf_url' => $sectionData['pdf_url'] ?? null,
                'display_order' => $order,
                'layout' => $sectionData['layout'] ?? 'full',
            ];

            if ($isExisting) {
                // Update existing section
                $sentIds[] = (int)$rawId;
                $this->sectionModel->update((int)$rawId, $sectionPayload);

                Logger::info('Section updated during page sync', [
                    'page_id' => $pageId,
                    'section_id' => $rawId,
                ]);
            } else {
                // Create new section
                $sectionPayload['page_id'] = $pageId;
                $newSection = $this->sectionModel->create($sectionPayload);
                $sentIds[] = (int)$newSection['id'];

                Logger::info('Section created during page sync', [
                    'page_id' => $pageId,
                    'section_id' => $newSection['id'],
                ]);
            }
        }

        // Delete sections that were removed from the editor
        foreach ($existingIds as $eid) {
            if (!in_array($eid, $sentIds)) {
                $this->sectionModel->delete($eid);

                Logger::info('Section deleted during page sync', [
                    'page_id' => $pageId,
                    'section_id' => $eid,
                ]);
            }
        }
    }
}
