<?php

namespace App\Queries\Dashboard;

use App\Models\QuizAttempt;
use Illuminate\Support\Carbon;

class TopPerformersQuery
{
    public function execute(): array
    {
        return QuizAttempt::with(['candidate', 'quiz'])
            ->whereNotNull('final_score')
            ->whereBetween('submitted_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->orderByDesc('final_score')
            ->limit(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'candidate_name' => $attempt->candidate->name,
                    'candidate_email' => $attempt->candidate->email,
                    'score' => $attempt->final_score,
                    'quiz_title' => $attempt->quiz->title,
                    'submitted_at' => $attempt->submitted_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }
}
