<?php

namespace App\Controllers;

use App\Services\EventService;
use App\Helpers\Response;
use App\Helpers\SessionManager;
use App\Middleware\RoleMiddleware;

class EventController
{
    private EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);

        $result = $this->eventService->getPublished($page, $perPage);
        Response::paginated($result['events'], $page, $perPage, $result['total']);
    }

    public function adminIndex(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 15);

        $result = $this->eventService->getAll($page, $perPage);
        Response::paginated($result['events'], $page, $perPage, $result['total']);
    }

    public function show(array $params): void
    {
        $event = $this->eventService->getById((int)$params['id']);
        if (!$event) {
            Response::notFound('Event not found');
        }
        Response::success($event);
    }

    public function showBySlug(array $params): void
    {
        $event = $this->eventService->getBySlug($params['slug']);
        if (!$event) {
            Response::notFound('Event not found');
        }
        Response::success($event);
    }

    public function store(): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->eventService->create($data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('CREATE_FAILED', $result['error'] ?? 'Failed to create event');
        }

        Response::success($result['event'], 'Event created successfully', 201);
    }

    public function update(array $params): void
    {
        $user = SessionManager::getUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->eventService->update((int)$params['id'], $data, $user['id']);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            }
            Response::error('UPDATE_FAILED', $result['error'] ?? 'Failed to update event');
        }

        Response::success($result['event'], 'Event updated successfully');
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();

        if (!RoleMiddleware::canDelete($user['role'])) {
            Response::forbidden('Only administrators can delete events');
        }

        $result = $this->eventService->delete((int)$params['id'], $user['id']);

        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }
}
