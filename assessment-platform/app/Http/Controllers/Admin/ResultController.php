<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\AttemptCameraSnapshot;
use App\Models\AttemptSuspiciousEvent;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function index(): Response
    {
        $quizzes = Quiz::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Quiz $quiz) => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $quiz->status->value,
                'attempts_count' => QuizAttempt::query()
                    ->where('quiz_id', $quiz->id)
                    ->whereNotNull('submitted_at')
                    ->count(),
            ]);

        return Inertia::render('Admin/Results/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function show(Quiz $quiz): Response
    {
        $user = Auth::user();
        $canSeeSuspicious = $user?->hasPermissionTo('results.viewSuspicious') ?? false;
        $canSeeSnapshots = $user?->hasPermissionTo('results.viewSnapshots') ?? false;
        $canExport = $user?->hasPermissionTo('results.export') ?? false;

        $paginator = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->with(['candidate'])
            ->withCount([
                'suspiciousEvents',
                'cameraSnapshots',
            ])
            ->orderByRaw('final_score IS NULL, final_score DESC')
            ->orderByDesc('auto_score')
            ->orderBy('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $attempts = $paginator->getCollection()->values()->map(function (QuizAttempt $attempt, int $index) use ($paginator, $canSeeSuspicious, $canSeeSnapshots): array {
            $rank = ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1;
            /** @var int|null $suspiciousCount */
            $suspiciousCount = $attempt->getAttribute('suspicious_events_count');
            /** @var int|null $snapshotsCount */
            $snapshotsCount = $attempt->getAttribute('camera_snapshots_count');

            return [
                'rank' => $rank,
                'id' => $attempt->id,
                'candidate' => [
                    'id' => $attempt->candidate?->id,
                    'name' => $attempt->candidate?->name,
                    'email' => $attempt->candidate?->email,
                ],
                'status' => $attempt->status->value,
                'rlhf_review_status' => $attempt->rlhf_review_status->value,
                'auto_score' => $attempt->auto_score !== null ? (float) $attempt->auto_score : null,
                'final_score' => $attempt->final_score !== null ? (float) $attempt->final_score : null,
                'started_at' => $attempt->started_at->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'time_taken_seconds' => $this->elapsedSeconds($attempt),
                'suspicious_events_count' => $canSeeSuspicious ? (int) ($suspiciousCount ?? 0) : null,
                'camera_snapshots_count' => $canSeeSnapshots ? (int) ($snapshotsCount ?? 0) : null,
            ];
        });

        return Inertia::render('Admin/Results/QuizResults', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score !== null ? (float) $quiz->passing_score : null,
            ],
            'attempts' => $attempts,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'permissions' => [
                'view_suspicious' => $canSeeSuspicious,
                'view_snapshots' => $canSeeSnapshots,
                'export' => $canExport,
            ],
        ]);
    }

    public function attempt(QuizAttempt $attempt): Response
    {
        $user = Auth::user();
        $canSeeSuspicious = $user?->hasPermissionTo('results.viewSuspicious') ?? false;
        $canSeeSnapshots = $user?->hasPermissionTo('results.viewSnapshots') ?? false;

        $attempt->load([
            'quiz',
            'candidate',
            'answers.question',
            'answers.selections.option',
            'answers.codingSubmission.testResults',
            'answers.rlhfReview',
            'answers.rlhfTurns',
        ]);

        $answers = $attempt->answers->values()->map(fn (AttemptAnswer $answer): array => $this->serializeAnswer($answer))->all();

        $suspicious = $canSeeSuspicious
            ? $attempt->suspiciousEvents()
                ->orderBy('occurred_at')
                ->get()
                ->values()
                ->map(fn (AttemptSuspiciousEvent $event): array => [
                    'id' => $event->id,
                    'event_type' => $event->event_type->value,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                    'metadata' => $event->metadata,
                ])
                ->all()
            : [];

        $snapshots = $canSeeSnapshots
            ? $attempt->cameraSnapshots()
                ->orderBy('captured_at')
                ->get()
                ->values()
                ->map(fn (AttemptCameraSnapshot $snapshot): array => [
                    'id' => $snapshot->id,
                    'url' => $snapshot->url,
                    'captured_at' => $snapshot->captured_at?->toIso8601String(),
                    'flagged' => (bool) $snapshot->flagged,
                ])
                ->all()
            : [];

        return Inertia::render('Admin/Results/AttemptDetail', [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'rlhf_review_status' => $attempt->rlhf_review_status->value,
                'auto_score' => $attempt->auto_score !== null ? (float) $attempt->auto_score : null,
                'final_score' => $attempt->final_score !== null ? (float) $attempt->final_score : null,
                'started_at' => $attempt->started_at->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'time_taken_seconds' => $this->elapsedSeconds($attempt),
            ],
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ],
            'candidate' => [
                'id' => $attempt->candidate?->id,
                'name' => $attempt->candidate?->name,
                'email' => $attempt->candidate?->email,
            ],
            'answers' => $answers,
            'suspicious_events' => $suspicious,
            'snapshots' => $snapshots,
            'permissions' => [
                'view_suspicious' => $canSeeSuspicious,
                'view_snapshots' => $canSeeSnapshots,
            ],
        ]);
    }

    public function export(Quiz $quiz): StreamedResponse
    {
        abort_unless(Auth::user()?->hasPermissionTo('results.export'), 403);

        $filename = 'results-'.$quiz->id.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($quiz) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'rank',
                'candidate_name',
                'candidate_email',
                'status',
                'auto_score',
                'final_score',
                'rlhf_review_status',
                'suspicious_event_count',
                'time_taken_seconds',
                'submitted_at',
            ]);

            $attempts = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->whereNotNull('submitted_at')
                ->with('candidate')
                ->withCount('suspiciousEvents')
                ->orderByRaw('final_score IS NULL, final_score DESC')
                ->orderByDesc('auto_score')
                ->orderBy('submitted_at')
                ->get();

            foreach ($attempts as $rank => $attempt) {
                /** @var int|null $susCount */
                $susCount = $attempt->getAttribute('suspicious_events_count');
                fputcsv($handle, [
                    $rank + 1,
                    $attempt->candidate?->name,
                    $attempt->candidate?->email,
                    $attempt->status->value,
                    $attempt->auto_score,
                    $attempt->final_score,
                    $attempt->rlhf_review_status->value,
                    (int) ($susCount ?? 0),
                    $this->elapsedSeconds($attempt),
                    $attempt->submitted_at?->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnswer(AttemptAnswer $answer): array
    {
        /** @var Question|null $question */
        $question = $answer->question;

        return [
            'id' => $answer->id,
            'question' => $question !== null ? [
                'id' => $question->id,
                'type' => $question->type->value,
                'stem' => $question->stem,
                'points' => (float) $question->points,
            ] : null,
            'status' => $answer->status->value,
            'auto_score' => $answer->auto_score !== null ? (float) $answer->auto_score : null,
            'reviewer_score' => $answer->reviewer_score !== null ? (float) $answer->reviewer_score : null,
            'time_spent_seconds' => $answer->time_spent_seconds,
            'selected_option_ids' => $answer->selections
                ->pluck('question_option_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'has_coding_submission' => $answer->codingSubmission !== null,
            'has_rlhf_turns' => $answer->rlhfTurns->isNotEmpty(),
            'rlhf_review' => $answer->rlhfReview !== null ? [
                'score' => (float) $answer->rlhfReview->score,
                'decision' => $answer->rlhfReview->decision,
                'finalized' => (bool) $answer->rlhfReview->finalized,
            ] : null,
            'drill_down_url' => $this->drillDownUrl($answer),
        ];
    }

    private function elapsedSeconds(QuizAttempt $attempt): ?int
    {
        if ($attempt->submitted_at === null) {
            return null;
        }

        return (int) $attempt->started_at->diffInSeconds($attempt->submitted_at);
    }

    private function drillDownUrl(AttemptAnswer $answer): ?string
    {
        if ($answer->question === null) {
            return null;
        }

        return match ($answer->question->type) {
            QuestionType::Rlhf => route('admin.rlhf.review.show', $answer->id),
            QuestionType::Coding => route('admin.coding.review.show', $answer->id),
            default => null,
        };
    }
}
