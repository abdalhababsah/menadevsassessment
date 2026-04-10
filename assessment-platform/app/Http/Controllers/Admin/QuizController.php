<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Quizzes\CreateQuizAction;
use App\Actions\Quizzes\DeleteQuizAction;
use App\Actions\Quizzes\DuplicateQuizAction;
use App\Actions\Quizzes\PublishQuizAction;
use App\Actions\Quizzes\UnpublishQuizAction;
use App\Actions\Quizzes\UpdateQuizSettingsAction;
use App\Exceptions\QuizPublishException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quizzes\StoreQuizRequest;
use App\Http\Requests\Admin\Quizzes\UpdateQuizSettingsRequest;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Quiz::class);

        // Per-quiz question counts via a single grouped query.
        $questionCounts = DB::table('quiz_section_questions')
            ->join('quiz_sections', 'quiz_sections.id', '=', 'quiz_section_questions.quiz_section_id')
            ->select('quiz_sections.quiz_id', DB::raw('count(*) as total'))
            ->groupBy('quiz_sections.quiz_id')
            ->pluck('total', 'quiz_sections.quiz_id');

        $quizzes = Quiz::query()
            ->withCount(['sections', 'invitations'])
            ->latest()
            ->get()
            ->map(function (Quiz $quiz) use ($questionCounts): array {
                /** @var int $sectionsCount */
                $sectionsCount = $quiz->getAttribute('sections_count') ?? 0;
                /** @var int $invitationsCount */
                $invitationsCount = $quiz->getAttribute('invitations_count') ?? 0;

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'status' => $quiz->status->value,
                    'status_label' => $quiz->status->label(),
                    'sections_count' => $sectionsCount,
                    'questions_count' => (int) ($questionCounts[$quiz->id] ?? 0),
                    'invitations_count' => $invitationsCount,
                    'created_at' => $quiz->created_at?->toDateString(),
                ];
            });

        return Inertia::render('Admin/Quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Quiz::class);

        return Inertia::render('Admin/Quizzes/Create');
    }

    public function store(StoreQuizRequest $request, CreateQuizAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $quiz = $action->handle(
            $request->validated('title'),
            $request->validated('description'),
            $user,
        );

        return redirect()->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Quiz created. Configure its settings and add sections.');
    }

    public function edit(Quiz $quiz): Response
    {
        $this->authorize('update', $quiz);

        return Inertia::render('Admin/Quizzes/Edit/Settings', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_seconds' => $quiz->time_limit_seconds,
                'passing_score' => $quiz->passing_score === null ? null : (float) $quiz->passing_score,
                'randomize_questions' => $quiz->randomize_questions,
                'randomize_options' => $quiz->randomize_options,
                'navigation_mode' => $quiz->navigation_mode->value,
                'camera_enabled' => $quiz->camera_enabled,
                'anti_cheat_enabled' => $quiz->anti_cheat_enabled,
                'max_fullscreen_exits' => $quiz->max_fullscreen_exits,
                'starts_at' => $quiz->starts_at?->toDateTimeString(),
                'ends_at' => $quiz->ends_at?->toDateTimeString(),
                'status' => $quiz->status->value,
            ],
        ]);
    }

    public function update(UpdateQuizSettingsRequest $request, Quiz $quiz, UpdateQuizSettingsAction $action): RedirectResponse
    {
        $action->handle($quiz, $request->validated());

        return redirect()->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Settings saved.');
    }

    public function builder(Quiz $quiz): Response
    {
        $this->authorize('update', $quiz);

        $quiz->load([
            'sections' => fn ($q) => $q->orderBy('position'),
            'sections.sectionQuestions' => fn ($q) => $q->orderBy('position'),
            'sections.sectionQuestions.question:id,type,stem,difficulty,points,version',
        ]);

        $sections = $this->serializeSections($quiz);

        return Inertia::render('Admin/Quizzes/Edit/Builder', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $quiz->status->value,
            ],
            'sections' => $sections,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeSections(Quiz $quiz): array
    {
        $result = [];

        foreach ($quiz->sections as $section) {
            /** @var QuizSection $section */
            $questions = [];
            foreach ($section->sectionQuestions as $sectionQuestion) {
                /** @var QuizSectionQuestion $sectionQuestion */
                $questions[] = $this->serializeSectionQuestion($sectionQuestion);
            }

            $result[] = [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'time_limit_seconds' => $section->time_limit_seconds,
                'position' => $section->position,
                'questions' => $questions,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSectionQuestion(QuizSectionQuestion $sectionQuestion): array
    {
        $question = $sectionQuestion->question;

        return [
            'id' => $sectionQuestion->id,
            'question_id' => $sectionQuestion->question_id,
            'question_version' => $sectionQuestion->question_version,
            'points_override' => $sectionQuestion->points_override === null ? null : (float) $sectionQuestion->points_override,
            'time_limit_override_seconds' => $sectionQuestion->time_limit_override_seconds,
            'position' => $sectionQuestion->position,
            'question' => $question === null ? null : [
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'stem' => Str::limit($question->stem, 140),
                'difficulty' => $question->difficulty->value,
                'points' => (float) $question->points,
                'version' => $question->version,
            ],
        ];
    }

    public function destroy(Quiz $quiz, DeleteQuizAction $action): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $action->handle($quiz);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted.');
    }

    public function publish(Quiz $quiz, PublishQuizAction $action): RedirectResponse
    {
        $this->authorize('publish', $quiz);

        try {
            $action->handle($quiz);
        } catch (QuizPublishException $e) {
            return back()->withErrors(['publish' => $e->getMessage()]);
        }

        return back()->with('success', 'Quiz published.');
    }

    public function unpublish(Quiz $quiz, UnpublishQuizAction $action): RedirectResponse
    {
        $this->authorize('publish', $quiz);

        $action->handle($quiz);

        return back()->with('success', 'Quiz unpublished.');
    }

    public function duplicate(Quiz $quiz, DuplicateQuizAction $action): RedirectResponse
    {
        $this->authorize('duplicate', $quiz);

        /** @var User $user */
        $user = request()->user();
        $copy = $action->handle($quiz, $user);

        return redirect()->route('admin.quizzes.edit', $copy)
            ->with('success', 'Quiz duplicated.');
    }
}
