<?php

namespace App\Queries\Dashboard;

use App\Enums\AttemptStatus;
use App\Enums\QuizStatus;
use App\Models\Candidate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Carbon;

class DashboardKpisQuery
{
    public function execute(): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();
        $endOfLastWeek = $endOfWeek->copy()->subWeek();

        return [
            'active_quizzes' => [
                'value' => Quiz::where('status', QuizStatus::Published)->count(),
                'sparkline' => $this->getQuizSparkline(),
            ],
            'total_candidates' => [
                'value' => Candidate::count(),
                'trend' => $this->getCandidateTrend(),
            ],
            'attempts_this_week' => [
                'value' => QuizAttempt::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
                'comparison' => QuizAttempt::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count(),
            ],
            'completion_rate' => [
                'value' => $this->getCompletionRate(),
            ],
        ];
    }

    private function getQuizSparkline(): array
    {
        // Weekly creation for the last 7 weeks
        return collect(range(6, 0))->map(function ($weeksAgo) {
            $start = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
            $end = Carbon::now()->subWeeks($weeksAgo)->endOfWeek();
            return Quiz::whereBetween('created_at', [$start, $end])->count();
        })->toArray();
    }

    private function getCandidateTrend(): float
    {
        $lastMonth = Candidate::whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])->count();
        $thisMonth = Candidate::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getCompletionRate(): float
    {
        $total = QuizAttempt::count();
        if ($total === 0) {
            return 0;
        }

        $completed = QuizAttempt::whereIn('status', [
            AttemptStatus::Submitted,
            AttemptStatus::AutoSubmitted,
        ])->count();

        return round(($completed / $total) * 100, 1);
    }
}
