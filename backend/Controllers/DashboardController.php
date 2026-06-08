<?php

namespace App\Controllers;

use App\Services\DashboardService;
use App\Helpers\Response;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function stats(): void
    {
        $stats = $this->dashboardService->getStats();
        Response::success($stats);
    }

    public function activity(): void
    {
        $limit = (int)($_GET['limit'] ?? 10);
        $activity = $this->dashboardService->getRecentActivity($limit);
        Response::success($activity);
    }
}
