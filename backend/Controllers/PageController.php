<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Middleware\RoleMiddleware;

class PageController
{
    private PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);
        $result = $this->pageService->getPublished($page, $perPage);
        Response::paginated($result['pages'], $page, $perPage, $result['total']);
    }

    public function adminIndex(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);
        $result = $this->pageService->getAll($page, $perPage);
        Response::paginated($result['pages'], $page, $perPage, $result['total']);
    }

    /**
     * Get page by ID — returns structured page data with raw sections.
     *
     * Used by the admin Edit Page screen. Returns the original
     * plain-text content (not generated HTML). The frontend renders
     * the same PageForm builder component with this data pre-populated.
     */
    public function show(array $params): void
    {
        $p = $this->pageService->getByIdWithSections((int)$params['id']);
        if (!$p) Response::notFound('Page not found');
        Response::success($p);
    }

    /**
     * Get page by slug — returns full page with enriched sections + SEO.
     *
     * Used by the public website for rendering. Sections include
     * content_html (generated from plain text). SEO metadata and
     * JSON-LD structured data are included.
     */
    public function showBySlug(array $params): void
    {
        $slug = $params['slug'];
        $page = $this->pageService->getFullPage($slug);
        if (!$page) Response::notFound('Page not found');
        Response::success($page);
    }

    public function store(): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->pageService->create($data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('CREATE_FAILED', $result['error'] ?? 'Failed to create page');
        }

        Response::success($result['page'], 'Page created successfully', 201);
    }

    public function update(array $params): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->pageService->update((int)$params['id'], $data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('UPDATE_FAILED', $result['error'] ?? 'Failed to update page');
        }

        Response::success($result['page'], 'Page updated successfully');
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();
        if (!RoleMiddleware::canDelete($user['role'])) {
            Response::forbidden('Only administrators can delete pages');
        }

        $result = $this->pageService->delete((int)$params['id'], $user['id']);
        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }
}
