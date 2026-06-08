<?php

namespace App\Controllers;

use App\Services\NewsService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Middleware\RoleMiddleware;

class NewsController
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);

        $result = $this->newsService->getPublished($page, $perPage);
        Response::paginated($result['news'], $page, $perPage, $result['total']);
    }

    public function adminIndex(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);

        $result = $this->newsService->getAll($page, $perPage);
        Response::paginated($result['news'], $page, $perPage, $result['total']);
    }

    public function show(array $params): void
    {
        $news = $this->newsService->getById((int)$params['id']);
        if (!$news) {
            Response::notFound('News article not found');
        }
        Response::success($news);
    }

    public function showBySlug(array $params): void
    {
        $news = $this->newsService->getBySlug($params['slug']);
        if (!$news) {
            Response::notFound('News article not found');
        }
        Response::success($news);
    }

    public function store(): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->newsService->create($data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('CREATE_FAILED', $result['error'] ?? 'Failed to create news article');
        }

        Response::success($result['news'], 'News article created successfully', 201);
    }

    public function update(array $params): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->newsService->update((int)$params['id'], $data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('UPDATE_FAILED', $result['error'] ?? 'Failed to update news article');
        }

        Response::success($result['news'], 'News article updated successfully');
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canDelete($user['role'])) {
            Response::forbidden('Only administrators can delete news articles');
        }

        $result = $this->newsService->delete((int)$params['id'], $user['id']);

        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }
}
