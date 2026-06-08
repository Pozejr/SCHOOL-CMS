<?php

namespace App\Services;

use App\Models\Page;
use App\Models\News;
use App\Models\Event;
use App\Models\File;
use App\Models\User;
use App\Models\ActivityLog;
use PDO;

class DashboardService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getStats(): array
    {
        // Consolidate 9 separate COUNT queries into a single query.
        // Uses PostgreSQL FILTER clause for conditional counts.
        $sql = "SELECT
            COUNT(*) FILTER (WHERE 1=1) AS total_pages,
            COUNT(*) FILTER (WHERE status = 'published') AS published_pages
            FROM pages WHERE deleted_at IS NULL";
        $stmt = $this->pdo->query($sql);
        $pageStats = $stmt->fetch();

        $sql = "SELECT
            COUNT(*) FILTER (WHERE 1=1) AS total_news,
            COUNT(*) FILTER (WHERE status = 'published') AS published_news
            FROM news WHERE deleted_at IS NULL";
        $stmt = $this->pdo->query($sql);
        $newsStats = $stmt->fetch();

        $sql = "SELECT
            COUNT(*) FILTER (WHERE 1=1) AS total_events,
            COUNT(*) FILTER (WHERE status = 'published') AS published_events,
            COUNT(*) FILTER (WHERE status = 'published' AND event_date >= NOW()) AS upcoming_events
            FROM events WHERE deleted_at IS NULL";
        $stmt = $this->pdo->query($sql);
        $eventStats = $stmt->fetch();

        $sql = "SELECT
            COUNT(*) AS total_files
            FROM files";
        $stmt = $this->pdo->query($sql);
        $fileStats = $stmt->fetch();

        $sql = "SELECT
            COUNT(*) AS active_users
            FROM users WHERE status = 'active'";
        $stmt = $this->pdo->query($sql);
        $userStats = $stmt->fetch();

        return [
            'total_pages'      => (int)$pageStats['total_pages'],
            'published_pages'  => (int)$pageStats['published_pages'],
            'total_news'       => (int)$newsStats['total_news'],
            'published_news'   => (int)$newsStats['published_news'],
            'total_events'     => (int)$eventStats['total_events'],
            'published_events' => (int)$eventStats['published_events'],
            'upcoming_events'  => (int)$eventStats['upcoming_events'],
            'total_files'      => (int)$fileStats['total_files'],
            'active_users'     => (int)$userStats['active_users'],
        ];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        $sql = "SELECT al.*, u.first_name, u.last_name, u.username
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
