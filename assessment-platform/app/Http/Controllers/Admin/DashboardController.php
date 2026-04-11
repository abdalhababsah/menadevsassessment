<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Queries\Dashboard\ChartDataQuery;
use App\Queries\Dashboard\DashboardKpisQuery;
use App\Queries\Dashboard\PendingTasksQuery;
use App\Queries\Dashboard\QuizzesSpotlightQuery;
use App\Queries\Dashboard\RecentActivityQuery;
use App\Queries\Dashboard\TopPerformersQuery;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(
        DashboardKpisQuery $kpiQuery,
        RecentActivityQuery $activityQuery,
        TopPerformersQuery $performersQuery,
        PendingTasksQuery $tasksQuery,
        QuizzesSpotlightQuery $quizzesQuery,
        ChartDataQuery $chartsQuery
    ): Response {
        $userId = auth()->id();
        $cacheKey = "dashboard_data_{$userId}";

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $kpiQuery,
            $activityQuery,
            $performersQuery,
            $tasksQuery,
            $quizzesQuery,
            $chartsQuery
        ) {
            return [
                'kpis' => $kpiQuery->execute(),
                'recent_activity' => $activityQuery->execute(),
                'top_performers' => $performersQuery->execute(),
                'pending_tasks' => $tasksQuery->execute(),
                'quizzes' => $quizzesQuery->execute(),
                'charts' => $chartsQuery->execute(),
                'online_candidates_count' => rand(5, 15), // Simulating live data as requested
            ];
        });

        return Inertia::render('Dashboard', [
            'data' => $data
        ]);
    }
}
