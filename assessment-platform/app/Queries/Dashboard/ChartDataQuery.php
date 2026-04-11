<?php

namespace App\Queries\Dashboard;

use App\Models\QuizAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChartDataQuery
{
    public function execute(): array
    {
        return [
            'attempts_over_time' => $this->getAttemptsTrend(),
            'score_distribution' => $this->getScoreDistribution(),
        ];
    }

    private function getAttemptsTrend(): array
    {
        // Last 30 days of attempts
        $days = collect(range(29, 0))->map(function ($daysAgo) {
            $date = Carbon::now()->subDays($daysAgo)->format('Y-m-d');
            return [
                'date' => $date,
                'count' => 0,
            ];
        });

        $stats = QuizAttempt::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return $days->map(function ($item) use ($stats) {
            if ($stats->has($item['date'])) {
                $item['count'] = $stats->get($item['date'])->count;
            }
            // Format for charts
            $item['name'] = Carbon::parse($item['date'])->format('M d');
            return $item;
        })->toArray();
    }

    private function getScoreDistribution(): array
    {
        $distribution = [
            ['name' => '0-25%', 'value' => 0, 'fill' => '#ef4444'], // Red
            ['name' => '25-50%', 'value' => 0, 'fill' => '#f97316'], // Orange
            ['name' => '50-75%', 'value' => 0, 'fill' => '#3b82f6'], // Blue
            ['name' => '75-100%', 'value' => 0, 'fill' => '#22c55e'], // Green
        ];

        $attempts = QuizAttempt::whereNotNull('final_score')
            ->select('final_score')
            ->get();

        foreach ($attempts as $attempt) {
            $score = (float) $attempt->final_score;
            if ($score < 25) {
                $distribution[0]['value']++;
            } elseif ($score < 50) {
                $distribution[1]['value']++;
            } elseif ($score < 75) {
                $distribution[2]['value']++;
            } else {
                $distribution[3]['value']++;
            }
        }

        return $distribution;
    }
}
