<?php

namespace App\Services;

use App\Models\News;
use App\Models\ActivityLog;
use App\Helpers\SlugGenerator;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Helpers\Validator;

class NewsService
{
    private News $newsModel;
    private ActivityLog $activityLog;

    public function __construct(News $newsModel, ActivityLog $activityLog)
    {
        $this->newsModel = $newsModel;
        $this->activityLog = $activityLog;
    }

    public function getAll(int $page = 1, int $perPage = 15): array
    {
        $news = $this->newsModel->findAllWithCreator($page, $perPage);
        $total = $this->newsModel->countAll();
        return ['news' => $news, 'total' => $total];
    }

    public function getPublished(int $page = 1, int $perPage = 15): array
    {
        $news = $this->newsModel->findPublished($page, $perPage);
        $total = $this->newsModel->countPublished();
        return ['news' => $news, 'total' => $total];
    }

    public function getById(int $id): ?array
    {
        return $this->newsModel->findById($id);
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->newsModel->findBySlug($slug);
    }

    public function create(array $data, int $userId): array
    {
        $validator = new Validator($data, [
            'title' => 'required|max:255',
            'content' => 'string',
            'status' => 'in:published,draft',
        ]);

        if (!$validator->validate()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $slug = SlugGenerator::generate($data['title'], fn($s) => $this->newsModel->slugExists($s));

        $newsData = [
            'title' => Security::sanitizeString($data['title']),
            'slug' => $slug,
            'content' => Security::sanitizeHtml($data['content'] ?? ''),
            'excerpt' => Security::sanitizeString($data['excerpt'] ?? ''),
            'featured_image' => $data['featured_image'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'published_at' => ($data['status'] ?? '') === 'published' ? date('c') : null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        $news = $this->newsModel->create($newsData);

        $this->activityLog->log(
            $userId,
            'create',
            'news',
            $news['id'],
            "Created news article: {$news['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        Logger::info('News article created', ['news_id' => $news['id'], 'title' => $news['title']]);

        return ['success' => true, 'news' => $news];
    }

    public function update(int $id, array $data, int $userId): array
    {
        $news = $this->newsModel->findById($id);
        if (!$news || $news['deleted_at']) {
            return ['success' => false, 'error' => 'News article not found'];
        }

        $updateData = [];
        
        if (isset($data['title'])) {
            $updateData['title'] = Security::sanitizeString($data['title']);
        }
        if (isset($data['content'])) {
            $updateData['content'] = Security::sanitizeHtml($data['content']);
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
            // Set published_at when first publishing
            if ($data['status'] === 'published' && $news['published_at'] === null) {
                $updateData['published_at'] = date('c');
            }
        }
        if (array_key_exists('featured_image', $data)) {
            $updateData['featured_image'] = $data['featured_image'];
        }
        if (isset($data['slug'])) {
            if ($this->newsModel->slugExists($data['slug'], $id)) {
                return ['success' => false, 'errors' => [['field' => 'slug', 'message' => 'Slug already exists']]];
            }
            $updateData['slug'] = Security::sanitizeString($data['slug']);
        }

        $updateData['updated_by'] = $userId;
        $updated = $this->newsModel->update($id, $updateData);

        $this->activityLog->log(
            $userId,
            'update',
            'news',
            $id,
            "Updated news article: " . ($updateData['title'] ?? $news['title']),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'news' => $updated];
    }

    public function delete(int $id, int $userId): array
    {
        $news = $this->newsModel->findById($id);
        if (!$news || $news['deleted_at']) {
            return ['success' => false, 'error' => 'News article not found'];
        }

        $this->newsModel->softDelete($id);

        $this->activityLog->log(
            $userId,
            'delete',
            'news',
            $id,
            "Deleted news article: {$news['title']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'News article deleted successfully'];
    }
}
