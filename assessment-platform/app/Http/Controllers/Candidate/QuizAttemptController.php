<?php

namespace App\Http\Controllers\Candidate;

use App\Actions\Attempts\AdvanceQuestionAction;
use App\Actions\Attempts\AdvanceSectionAction;
use App\Actions\Attempts\RecordCodingAnswerAction;
use App\Actions\Attempts\RecordSelectionAnswerAction;
use App\Actions\Attempts\RetreatQuestionAction;
use App\Actions\Attempts\StartQuizAttemptAction;
use App\Actions\Attempts\SubmitQuizAttemptAction;
use App\Enums\AnswerStatus;
use App\Enums\QuestionType;
use App\Enums\QuizNavigationMode;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function run(Request $request): Response|RedirectResponse
    {
        $attempt = $this->resolveAttempt($request)->loadMissing('currentQuestion');

        if ($attempt->currentQuestion?->type === QuestionType::Rlhf) {
            return redirect()->route('candidate.quiz.rlhf.show');
        }

        return Inertia::render('Candidate/Quiz/Runner');
    }

    public function start(Request $request, StartQuizAttemptAction $action): JsonResponse
    {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        $token = (string) $request->session()->get('quiz_invitation_token', '');
        /** @var QuizInvitation|null $invitation */
        $invitation = QuizInvitation::query()
            ->where('token', $token)
            ->with('quiz.sections.sectionQuestions')
            ->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return response()->json([
                'message' => 'Your invitation is no longer valid.',
            ], 422);
        }

        $attempt = $action->handle(
            $invitation->quiz,
            $candidate,
            $invitation,
            $request->ip(),
            $request->userAgent(),
        );

        $request->session()->put('quiz_attempt_id', $attempt->id);
        $attempt->loadMissing('currentQuestion');

        return response()->json([
            'attempt_id' => $attempt->id,
            'run_url' => $attempt->currentQuestion?->type === QuestionType::Rlhf
                ? route('candidate.quiz.rlhf.show')
                : route('candidate.quiz.run'),
            'state' => $this->serializeState($attempt),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        return response()->json($this->serializeState($this->resolveAttempt($request)));
    }

    public function submitAnswer(
        Request $request,
        RecordSelectionAnswerAction $selectionAction,
        RecordCodingAnswerAction $codingAction,
    ): JsonResponse {
        $attempt = $this->resolveAttempt($request);

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_ids' => ['array'],
            'option_ids.*' => ['integer'],
            'code' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $question = Question::query()->findOrFail($data['question_id']);
        /** @var AttemptAnswer $answer */
        $answer = $attempt->answers()->where('question_id', $question->id)->firstOrFail();

        $timeSpent = (int) ($data['time_spent_seconds'] ?? $this->elapsedSeconds($attempt->question_started_at));

        if (in_array($question->type, [QuestionType::SingleSelect, QuestionType::MultiSelect], true)) {
            $selectionAction->handle($answer, $data['option_ids'] ?? [], $timeSpent);
        } elseif ($question->type === QuestionType::Coding) {
            $codingAction->handle(
                $answer,
                (string) ($data['language'] ?? ''),
                (string) ($data['code'] ?? ''),
                $timeSpent,
            );
        }

        return response()->json([
            'answer' => $this->serializeAnswer($answer->fresh(['selections', 'codingSubmission']) ?? $answer),
        ]);
    }

    public function previousQuestion(Request $request, RetreatQuestionAction $action): JsonResponse
    {
        $attempt = $this->resolveAttempt($request)->loadMissing('quiz');

        if ($attempt->quiz->navigation_mode !== QuizNavigationMode::Free) {
            return response()->json([
                'message' => 'Backward navigation is disabled for this assessment.',
            ], 403);
        }

        return response()->json($this->serializeState($action->handle($attempt)));
    }

    public function nextQuestion(Request $request, AdvanceQuestionAction $action): JsonResponse
    {
        return response()->json($this->serializeState($action->handle($this->resolveAttempt($request))));
    }

    public function nextSection(Request $request, AdvanceSectionAction $action): JsonResponse
    {
        return response()->json($this->serializeState($action->handle($this->resolveAttempt($request))));
    }

    public function confirmSubmit(Request $request): Response
    {
        $attempt = $this->resolveAttempt($request);
        $attempt->loadMissing([
            'quiz',
            'answers.question',
            'answers.selections',
            'answers.codingSubmission',
            'answers.rlhfTurns',
        ]);

        $total = $attempt->answers->count();
        $answered = $attempt->answers->where('status', AnswerStatus::Answered)->count();

        return Inertia::render('Candidate/Quiz/ConfirmSubmit', [
            'attempt' => [
                'id' => $attempt->id,
            ],
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ],
            'counts' => [
                'total_questions' => $total,
                'answered' => $answered,
                'unanswered' => max(0, $total - $answered),
            ],
        ]);
    }

    public function finalSubmit(Request $request, SubmitQuizAttemptAction $action): JsonResponse
    {
        $submitted = $action->handle($this->resolveAttempt($request));

        return response()->json([
            'submitted' => true,
            'exit_fullscreen' => true,
            'redirect' => route('candidate.quiz.submitted'),
            'attempt' => [
                'id' => $submitted->id,
                'status' => $submitted->status->value,
                'submitted_at' => $submitted->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    public function submitted(Request $request): Response|RedirectResponse
    {
        /** @var Candidate|null $candidate */
        $candidate = Auth::guard('candidate')->user();
        $attemptId = (int) $request->session()->get('quiz_attempt_id', 0);

        if ($candidate === null || $attemptId < 1) {
            return redirect()->route('candidate.pre-quiz');
        }

        $attempt = QuizAttempt::query()->with('quiz')->find($attemptId);

        if ($attempt === null || $attempt->candidate_id !== $candidate->id) {
            return redirect()->route('candidate.pre-quiz');
        }

        if ($attempt->isInProgress()) {
            return redirect()->route('candidate.quiz.run');
        }

        return Inertia::render('Candidate/Quiz/Submitted', [
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    private function resolveAttempt(Request $request): QuizAttempt
    {
        $attempt = $request->attributes->get('quizAttempt');

        if ($attempt instanceof QuizAttempt) {
            return $attempt;
        }

        return QuizAttempt::query()->findOrFail((int) $request->session()->get('quiz_attempt_id'));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeState(QuizAttempt $attempt): array
    {
        $attempt->loadMissing([
            'quiz.sections.sectionQuestions.question',
            'currentSection.sectionQuestions.question',
            'currentQuestion.options',
            'currentQuestion.codingConfig',
            'answers.selections',
            'answers.codingSubmission',
        ]);

        $quiz = $attempt->quiz;
        $sections = $quiz->sections;
        $orderedQuestions = $sections
            ->flatMap(fn (QuizSection $section) => $section->sectionQuestions)
            ->values();

        $currentSectionIndex = $sections->search(fn (QuizSection $section) => $section->id === $attempt->current_section_id);
        $currentSectionIndex = $currentSectionIndex === false ? 0 : $currentSectionIndex;
        /** @var QuizSection $currentSection */
        $currentSection = $sections[$currentSectionIndex];

        $sectionQuestions = $currentSection->sectionQuestions->values();
        $currentQuestionIndex = $sectionQuestions->search(
            fn (QuizSectionQuestion $sectionQuestion) => $sectionQuestion->question_id === $attempt->current_question_id
        );
        $currentQuestionIndex = $currentQuestionIndex === false ? 0 : $currentQuestionIndex;

        /** @var QuizSectionQuestion $currentSectionQuestion */
        $currentSectionQuestion = $sectionQuestions[$currentQuestionIndex];
        $currentQuestion = $currentSectionQuestion->question;
        $answer = $attempt->answers->firstWhere('question_id', $currentQuestion->id);

        $globalQuestionIndex = $orderedQuestions->search(
            fn (QuizSectionQuestion $sectionQuestion) => $sectionQuestion->id === $currentSectionQuestion->id
        );
        $globalQuestionIndex = $globalQuestionIndex === false ? 0 : $globalQuestionIndex;
        $totalQuestions = $orderedQuestions->count();

        $questionTimeLimit = $currentSectionQuestion->time_limit_override_seconds ?? $currentQuestion->time_limit_seconds;

        return [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'navigation_mode' => $quiz->navigation_mode->value,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'section' => [
                'id' => $currentSection->id,
                'title' => $currentSection->title,
                'position' => $currentSectionIndex + 1,
                'total_sections' => $sections->count(),
                'time_limit_seconds' => $currentSection->time_limit_seconds,
                'remaining_seconds' => $this->remainingSeconds($attempt->section_started_at, $currentSection->time_limit_seconds),
                'question_count' => $sectionQuestions->count(),
            ],
            'question' => $this->serializeQuestion(
                $currentQuestion,
                $currentSectionQuestion->question_version,
                $questionTimeLimit,
                $this->remainingSeconds($attempt->question_started_at, $questionTimeLimit),
            ),
            'answer' => $answer ? $this->serializeAnswer($answer) : null,
            'timers' => [
                'quiz_remaining_seconds' => $this->remainingSeconds($attempt->started_at, $quiz->time_limit_seconds),
                'section_remaining_seconds' => $this->remainingSeconds($attempt->section_started_at, $currentSection->time_limit_seconds),
                'question_remaining_seconds' => $this->remainingSeconds($attempt->question_started_at, $questionTimeLimit),
            ],
            'progress' => [
                'question_index' => $currentQuestionIndex + 1,
                'questions_in_section' => $sectionQuestions->count(),
                'global_question_index' => $globalQuestionIndex + 1,
                'total_questions' => $totalQuestions,
            ],
            'navigation' => [
                'mode' => $quiz->navigation_mode->value,
                'can_go_previous' => $quiz->navigation_mode === QuizNavigationMode::Free && $globalQuestionIndex > 0,
                'has_next_question' => $globalQuestionIndex < ($totalQuestions - 1),
                'has_next_section' => $currentSectionIndex < ($sections->count() - 1),
                'is_last_question_in_section' => $currentQuestionIndex === ($sectionQuestions->count() - 1),
                'is_last_question' => $globalQuestionIndex === ($totalQuestions - 1),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQuestion(
        Question $question,
        int $questionVersion,
        ?int $timeLimitSeconds,
        ?int $remainingSeconds,
    ): array {
        $question->loadMissing(['options', 'codingConfig']);

        return [
            'id' => $question->id,
            'version' => $questionVersion,
            'type' => $question->type->value,
            'stem' => $question->stem,
            'instructions' => $question->instructions,
            'points' => (float) $question->points,
            'time_limit_seconds' => $timeLimitSeconds,
            'remaining_seconds' => $remainingSeconds,
            'options' => $question->type !== QuestionType::Coding
                ? $question->options->sortBy('position')->values()->map(fn ($option) => [
                    'id' => $option->id,
                    'content_type' => $option->content_type,
                    'content' => $option->content,
                    'position' => $option->position,
                ])->values()
                : [],
            'coding' => $question->type === QuestionType::Coding && $question->codingConfig !== null
                ? [
                    'allowed_languages' => $question->codingConfig->allowed_languages,
                    'starter_code' => $question->codingConfig->starter_code,
                    'time_limit_ms' => $question->codingConfig->time_limit_ms,
                    'memory_limit_mb' => $question->codingConfig->memory_limit_mb,
                ]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnswer(AttemptAnswer $answer): array
    {
        $answer->loadMissing(['selections', 'codingSubmission']);

        return [
            'id' => $answer->id,
            'status' => $answer->status->value,
            'selected_option_ids' => $answer->selections->pluck('question_option_id')->map(
                fn (int|string $optionId) => (int) $optionId
            )->values(),
            'coding' => $answer->codingSubmission ? [
                'language' => $answer->codingSubmission->language,
                'code' => $answer->codingSubmission->code,
            ] : null,
        ];
    }

    private function remainingSeconds(?Carbon $startedAt, ?int $timeLimitSeconds): ?int
    {
        if ($timeLimitSeconds === null) {
            return null;
        }

        if ($startedAt === null) {
            return $timeLimitSeconds;
        }

        return max(0, $timeLimitSeconds - (int) $startedAt->diffInSeconds(now()));
    }

    private function elapsedSeconds(?Carbon $startedAt): int
    {
        if ($startedAt === null) {
            return 0;
        }

        return max(0, (int) $startedAt->diffInSeconds(now()));
    }
}
