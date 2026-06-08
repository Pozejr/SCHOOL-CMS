<?php

namespace App\Services;

use App\Models\PageTemplate;
use App\Models\PageSection;
use App\Helpers\Logger;

class TemplateService
{
    private PageTemplate $templateModel;
    private PageSection $sectionModel;

    public function __construct(PageTemplate $templateModel, PageSection $sectionModel)
    {
        $this->templateModel = $templateModel;
        $this->sectionModel = $sectionModel;
    }

    public function getAll(): array
    {
        $templates = $this->templateModel->findAllTemplates();
        // Decode JSON sections for each template
        return array_map(function ($t) {
            if (is_string($t['sections'])) {
                $t['sections'] = json_decode($t['sections'], true) ?? [];
            }
            return $t;
        }, $templates);
    }

    public function getBySlug(string $slug): ?array
    {
        $template = $this->templateModel->findBySlug($slug);
        if (!$template) return null;
        if (is_string($template['sections'])) {
            $template['sections'] = json_decode($template['sections'], true) ?? [];
        }
        return $template;
    }

    /**
     * Apply a template to a page — creates empty sections from template definition.
     */
    public function applyToPage(int $pageId, string $templateSlug, int $userId): array
    {
        $template = $this->templateModel->findBySlug($templateSlug);
        if (!$template) {
            return ['success' => false, 'error' => 'Template not found'];
        }

        $sections = is_string($template['sections']) 
            ? json_decode($template['sections'], true) 
            : $template['sections'];

        if (empty($sections)) {
            return ['success' => false, 'error' => 'Template has no sections'];
        }

        // Delete existing sections for this page
        $this->sectionModel->deleteByPageId($pageId);

        // Create sections from template
        $created = [];
        foreach ($sections as $order => $sectionDef) {
            $data = [
                'page_id' => $pageId,
                'section_type' => $sectionDef['section_type'] ?? 'text',
                'title' => $sectionDef['title'] ?? '',
                'content' => '',
                'layout' => $sectionDef['layout'] ?? 'full',
                'display_order' => $order,
            ];
            $created[] = $this->sectionModel->create($data);
        }

        Logger::info('Template applied to page', [
            'template' => $templateSlug,
            'page_id' => $pageId,
            'sections_created' => count($created),
        ]);

        return [
            'success' => true,
            'message' => "Applied template with " . count($created) . " sections",
            'sections' => $created,
        ];
    }
}
