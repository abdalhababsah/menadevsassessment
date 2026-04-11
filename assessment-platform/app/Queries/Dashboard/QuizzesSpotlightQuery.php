<?php

namespace App\Queries\Dashboard;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Enums\AttemptStatus;

class QuizzesSpotlightQuery
{
    public function execute(): array
    {
        return Quiz::withCount(['attempts' => function ($query) {
                $query->whereIn('status', [AttemptStatus::Submitted, AttemptStatus::AutoSubmitted]);
            }])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($quiz) {
                $avgScore = QuizAttempt::where('quiz_id', $quiz->id)
                    ->whereNotNull('final_score')
                    ->avg('final_score') ?? 0;

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'status' => $quiz->status,
                    'attempts_count' => $quiz->attempts_count,
                    'avg_score' => round($avgScore, 1),
                    'last_activity' => $quiz->updated_at->diffForHumans(),
                ];
            })
            ->toArray();
    }
}
