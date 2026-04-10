<?php

namespace App\Http\Controllers\Candidate;

use App\Actions\Attempts\AdvanceQuestionAction;
use App\Actions\Attempts\AdvanceSectionAction;
use App\Actions\Attempts\RecordCodingAnswerAction;
use App\Actions\Attempts\RecordSelectionAnswerAction;
use App\Actions\Attempts\StartQuizAttemptAction;
use App\Actions\Attempts\SubmitQuizAttemptAction;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Candidate;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function run(): Response
    {
        return Inertia::render('Candidate/Quiz/Runner');
    }

    public function start(
        Request $request,
        StartQuizAttemptAction $action,
    ): RedirectResponse {
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        $token = (string) $request->session()->get('quiz_invitation_token', '');
        /** @var QuizInvitation|null $invitation */
        $invitation = QuizInvitation::where('token', $token)->with('quiz.sections.sectionQuestions')->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return redirect()->route('candidate.invitations.show', $token)
                ->withErrors(['invitation' => 'Your invitation is no longer valid.']);
        }

        $attempt = $action->handle(
            $invitation->quiz,
            $candidate,
            $invitation,
            $request->ip(),
            $request->userAgent(),
        );

        $request->session()->put('quiz_attempt_id', $attempt->id);

        return redirect()->route('candidate.quiz.run');
    }

    public function current(Request $request): JsonResponse
    {
        $attempt = $this->resolveAttempt($request);

        return response()->json($this->serializeState($attempt));
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

        $question = Question::findOrFail($data['question_id']);

        /** @var AttemptAnswer $answer */
        $answer = $attempt->answers()->where('question_id', $question->id)->firstOrFail();

        $timeSpent = $data['time_spent_seconds'] ?? null;

        if (in_array($question->type, [QuestionType::SingleSelect, QuestionType::MultiSelect], true)) {
            $selectionAction->handle($answer, $data['option_ids'] ?? [], $timeSpent);
        } elseif ($question->type === QuestionType::Coding) {
            $codingAction->handle($answer, (string) $data['language'], (string) $data['code'], $timeSpent);
        }

        return response()->json([
            'answer' => $this->serializeAnswer($answer->fresh(['selections', 'codingSubmission'])),
        ]);
    }

    public function nextQuestion(Request $request, AdvanceQuestionAction $action): JsonResponse
    {
        $attempt = $this->resolveAttempt($request);
        $updated = $action->handle($attempt);

        return response()->json($this->serializeState($updated));
    }

    public function nextSection(Request $request, AdvanceSectionAction $action): JsonResponse
    {
        $attempt = $this->resolveAttempt($request);
        $updated = $action->handle($attempt);

        return response()->json($this->serializeState($updated));
    }

    public function submit(Request $request, SubmitQuizAttemptAction $action): JsonResponse
    {
        $attempt = $this->resolveAttempt($request);
        $submitted = $action->handle($attempt);

        return response()->json([
            'submitted' => true,
            'attempt' => [
                'id' => $submitted->id,
                'status' => $submitted->status->value,
                'submitted_at' => optional($submitted->submitted_at)->toIso8601String(),
                'final_score' => $submitted->final_score,
            ],
        ]);
    }

    private function resolveAttempt(Request $request): QuizAttempt
    {
        $attemptId = (int) $request->session()->get('quiz_attempt_id', 0);
        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        $attempt = QuizAttempt::with(['quiz.sections.sectionQuestions', 'answers'])
            ->findOrFail($attemptId);

        abort_unless($attempt->candidate_id === $candidate->id, 403);

        return $attempt;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeState(QuizAttempt $attempt): array
    {
        $attempt->loadMissing([
            'quiz.sections.sectionQuestions.question',
            'currentSection.sectionQuestions',
            'currentQuestion.options',
            'currentQuestion.codingConfig',
            'answers.selections',
            'answers.codingSubmission',
        ]);

        $quiz = $attempt->quiz;
        $sections = $quiz->sections;

        $currentSectionIndex = $sections->search(fn ($section) => $section->id === $attempt->current_section_id);
        $currentSectionIndex = $currentSectionIndex === false ? 0 : $currentSectionIndex;
        $currentSection = $sections[$currentSectionIndex];

        $sectionQuestions = $currentSection->sectionQuestions;
        $currentQuestionIndex = $sectionQuestions->search(fn ($sq) => $sq->question_id === $attempt->current_question_id);
        $currentQuestionIndex = $currentQuestionIndex === false ? 0 : $currentQuestionIndex;
        $sectionQuestion = $sectionQuestions[$currentQuestionIndex];
        $currentQuestion = $sectionQuestion->question;

        $answer = $attempt->answers->firstWhere('question_id', $currentQuestion->id);

        return [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'started_at' => optional($attempt->started_at)->toIso8601String(),
                'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
                'navigation_mode' => $quiz->navigation_mode->value,
                'time_limit_seconds' => $quiz->time_limit_seconds,
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
                'question_count' => $sectionQuestions->count(),
            ],
            'question' => $this->serializeQuestion(
                $currentQuestion,
                $sectionQuestion->question_version,
                $sectionQuestion->time_limit_override_seconds
            ),
            'answer' => $answer ? $this->serializeAnswer($answer) : null,
            'progress' => [
                'question_index' => $currentQuestionIndex + 1,
                'questions_in_section' => $sectionQuestions->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQuestion(Question $question, int $questionVersion, ?int $overrideQuestionTimeLimit): array
    {
        $question->loadMissing(['options', 'codingConfig']);

        $effectiveQuestionTimeLimit = $overrideQuestionTimeLimit ?? $question->time_limit_seconds;

        return [
            'id' => $question->id,
            'version' => $questionVersion,
            'type' => $question->type->value,
            'stem' => $question->stem,
            'instructions' => $question->instructions,
            'points' => (float) $question->points,
            'time_limit_seconds' => $effectiveQuestionTimeLimit,
            'options' => $question->type !== QuestionType::Coding
                ? $question->options->sortBy('position')->values()->map(fn ($o) => [
                    'id' => $o->id,
                    'content_type' => $o->content_type,
                    'content' => $o->content,
                    'position' => $o->position,
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
            'selected_option_ids' => $answer->selections->pluck('question_option_id')->values(),
            'coding' => $answer->codingSubmission ? [
                'language' => $answer->codingSubmission->language,
                'code' => $answer->codingSubmission->code,
            ] : null,
        ];
    }
}
