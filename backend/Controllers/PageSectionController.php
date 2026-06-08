<?php

namespace App\Controllers;

use App\Services\PageSectionService;
use App\Services\TemplateService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Middleware\RoleMiddleware;

class PageSectionController
{
    private PageSectionService $sectionService;
    private TemplateService $templateService;

    public function __construct(PageSectionService $sectionService, TemplateService $templateService)
    {
        $this->sectionService = $sectionService;
        $this->templateService = $templateService;
    }

    public function index(array $params): void
    {
        $pageId = (int)$params['page_id'];
        $sections = $this->sectionService->getByPageId($pageId);
        Response::success($sections);
    }

    public function store(array $params): void
    {
        $user = SessionManager::getUser();
        $pageId = (int)$params['page_id'];
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->sectionService->create($pageId, $data, $user['id']);

        if (!$result['success']) {
            Response::error('CREATE_FAILED', $result['error'] ?? 'Failed to create section');
        }

        Response::success($result['section'], 'Section created', 201);
    }

    public function update(array $params): void
    {
        $user = SessionManager::getUser();
        $id = (int)$params['id'];
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->sectionService->update($id, $data, $user['id']);

        if (!$result['success']) {
            Response::error('UPDATE_FAILED', $result['error'] ?? 'Failed to update section');
        }

        Response::success($result['section'], 'Section updated');
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();
        $id = (int)$params['id'];

        $result = $this->sectionService->delete($id, $user['id']);

        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }

    public function reorder(array $params): void
    {
        $user = SessionManager::getUser();
        $pageId = (int)$params['page_id'];
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $sectionIds = $data['section_ids'] ?? [];

        if (empty($sectionIds)) {
            Response::validationError([['field' => 'section_ids', 'message' => 'Section IDs required']]);
        }

        $result = $this->sectionService->reorder($pageId, $sectionIds, $user['id']);
        Response::success(null, $result['message']);
    }

    // Templates
    public function templates(): void
    {
        $templates = $this->templateService->getAll();
        Response::success($templates);
    }

    public function templateBySlug(array $params): void
    {
        $template = $this->templateService->getBySlug($params['slug']);
        if (!$template) {
            Response::notFound('Template not found');
        }
        Response::success($template);
    }

    public function applyTemplate(array $params): void
    {
        $user = SessionManager::getUser();
        $pageId = (int)$params['page_id'];
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $templateSlug = $data['template_slug'] ?? '';

        if (empty($templateSlug)) {
            Response::validationError([['field' => 'template_slug', 'message' => 'Template slug required']]);
        }

        $result = $this->templateService->applyToPage($pageId, $templateSlug, $user['id']);

        if (!$result['success']) {
            Response::error('TEMPLATE_FAILED', $result['error']);
        }

        Response::success($result['sections'], $result['message']);
    }
}
