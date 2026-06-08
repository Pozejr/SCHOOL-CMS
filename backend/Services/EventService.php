<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ActivityLog;
use App\Helpers\SlugGenerator;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Helpers\Validator;

class EventService
{
    private Event $eventModel;
    private ActivityLog $activityLog;

    public function __construct(Event $eventModel, ActivityLog $activityLog)
    {
        $this->eventModel = $eventModel;
        $this->activityLog = $activityLog;
    }

    public function getAll(int $page = 1, int $perPage = 15): array
    {
        $events = $this->eventModel->findAllWithCreator($page, $perPage);
        $total = $this->eventModel->countAll();
        return ['events' => $events, 'total' => $total];
    }

    public function getPublished(int $page = 1, int $perPage = 15): array
    {
        $events = $this->eventModel->findPublished($page, $perPage);
        $total = $this->eventModel->countPublished();
        return ['events' => $events, 'total' => $total];
    }

    public function getUpcoming(int $limit = 5): array
    {
        return $this->eventModel->findUpcoming($limit);
    }

    public function getById(int $id): ?array
    {
        return $this->eventModel->findById($id);
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->eventModel->findBySlug($slug);
    }

    public function create(array $data, int $userId): array
    {
        $validator = new Validator($data, [
            'title' => 'required|max:255',
            'event_date' => 'required',
            'status' => 'in:published,draft',
        ]);

        if (!$validator->validate()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $slug = SlugGenerator::generate($data['title'], fn($s) => $this->eventModel->slugExists($s));

        $eventData = [
            'title' => Security::sanitizeString($data['title']),
            'slug' => $slug,
            'description' => Security::sanitizeHtml($data['description'] ?? ''),
            'venue' => Security::sanitizeString($data['venue'] ?? ''),
            'event_date' => $data['event_date'],
            'end_date' => $data['end_date'] ?? null,
            'featured_image' => $data['featured_image'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        $event = $this->eventModel->create($eventData);

        $this->activityLog->log(
            $userId,
            'create',
            'event',
            $event['id'],
            "Created event: {$event['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        Logger::info('Event created', ['event_id' => $event['id'], 'title' => $event['title']]);

        return ['success' => true, 'event' => $event];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $event = $this->eventModel->findById($id);
        if (!$event || $event['deleted_at']) {
            return ['success' => false, 'error' => 'Event not found'];
        }

        $updateData = [];
        
        if (isset($data['title'])) {
            $updateData['title'] = Security::sanitizeString($data['title']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = Security::sanitizeHtml($data['description']);
        }
        if (isset($data['venue'])) {
            $updateData['venue'] = Security::sanitizeString($data['venue']);
        }
        if (isset($data['event_date'])) {
            $updateData['event_date'] = $data['event_date'];
        }
        if (array_key_exists('end_date', $data)) {
            $updateData['end_date'] = $data['end_date'];
        }
        if (isset($data['status'])) {
            $validator = new Validator($data, ['status' => 'in:published,draft']);
            if (!$validator->validate()) {
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('featured_image', $data)) {
            $updateData['featured_image'] = $data['featured_image'];
        }

        $updateData['updated_by'] = $userId;
        $updated = $this->eventModel->update($id, $updateData);

        $this->activityLog->log(
            $userId,
            'update',
            'event',
            $id,
            "Updated event: " . ($updateData['title'] ?? $event['title']),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'event' => $updated];
    }

    public function delete(int $id, int $userId): array
    {
        $event = $this->eventModel->findById($id);
        if (!$event || $event['deleted_at']) {
            return ['success' => false, 'error' => 'Event not found'];
        }

        $this->eventModel->softDelete($id);

        $this->activityLog->log(
            $userId,
            'delete',
            'event',
            $id,
            "Deleted event: {$event['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'Event deleted successfully'];
    }
}
