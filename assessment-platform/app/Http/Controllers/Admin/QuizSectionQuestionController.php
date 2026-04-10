<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Questions\CreateCodingQuestionAction;
use App\Actions\Questions\CreateMultiSelectQuestionAction;
use App\Actions\Questions\CreateSingleSelectQuestionAction;
use App\Actions\Quizzes\AttachQuestionToSectionAction;
use App\Actions\Quizzes\DetachQuestionFromSectionAction;
use App\Actions\Quizzes\ReorderSectionQuestionsAction;
use App\Actions\Quizzes\UpdateSectionQuestionPivotAction;
use App\Actions\Rlhf\CreateRlhfQuestionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Questions\StoreCodingQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreMultiSelectQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreRlhfQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreSingleSelectQuestionRequest;
use App\Http\Requests\Admin\Quizzes\AttachQuestionRequest;
use App\Http\Requests\Admin\Quizzes\ReorderSectionQuestionsRequest;
use App\Http\Requests\Admin\Quizzes\UpdateSectionQuestionPivotRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizSectionQuestionController extends Controller
{
    public function bankSearch(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorize('update', $quiz);

        $query = Question::query()->with('tags');

        if ($term = $request->query('q')) {
            /** @var string $term */
            $query->where('stem', 'like', '%'.$term.'%');
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($difficulty = $request->query('difficulty')) {
            $query->where('difficulty', $difficulty);
        }

        $results = $query->latest()->limit(50)->get()->map(function (Question $question): array {
            return [
                'id' => $question->id,
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'difficulty' => $question->difficulty->value,
                'stem' => Str::limit($question->stem, 140),
                'points' => (float) $question->points,
                'version' => $question->version,
                'tags' => $question->tags->pluck('name')->all(),
            ];
        });

        return response()->json(['questions' => $results]);
    }

    public function attach(
        AttachQuestionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        AttachQuestionToSectionAction $action,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        $question = Question::findOrFail($request->validated('question_id'));

        $action->handle(
            $section,
            $question,
            $request->validated('points_override'),
            $request->validated('time_limit_override_seconds'),
        );

        return back()->with('success', 'Question added to section.');
    }

    public function detach(
        Quiz $quiz,
        QuizSection $section,
        QuizSectionQuestion $sectionQuestion,
        DetachQuestionFromSectionAction $action,
    ): RedirectResponse {
        $this->authorize('update', $quiz);
        abort_unless($section->quiz_id === $quiz->id, 404);
        abort_unless($sectionQuestion->quiz_section_id === $section->id, 404);

        $action->handle($sectionQuestion);

        return back()->with('success', 'Question removed.');
    }

    public function updatePivot(
        UpdateSectionQuestionPivotRequest $request,
        Quiz $quiz,
        QuizSection $section,
        QuizSectionQuestion $sectionQuestion,
        UpdateSectionQuestionPivotAction $action,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);
        abort_unless($sectionQuestion->quiz_section_id === $section->id, 404);

        $action->handle(
            $sectionQuestion,
            $request->validated('points_override'),
            $request->validated('time_limit_override_seconds'),
        );

        return back()->with('success', 'Overrides updated.');
    }

    public function reorder(
        ReorderSectionQuestionsRequest $request,
        Quiz $quiz,
        QuizSection $section,
        ReorderSectionQuestionsAction $action,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        $action->handle($section, $request->validated('section_question_ids'));

        return back()->with('success', 'Questions reordered.');
    }

    public function createInlineSingleSelect(
        StoreSingleSelectQuestionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        CreateSingleSelectQuestionAction $createAction,
        AttachQuestionToSectionAction $attachAction,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $section, $createAction, $attachAction, $user): void {
            $question = $createAction->handle($request->toData(), $user);
            $attachAction->handle($section, $question);
        });

        return back()->with('success', 'Question created and added to section.');
    }

    public function createInlineMultiSelect(
        StoreMultiSelectQuestionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        CreateMultiSelectQuestionAction $createAction,
        AttachQuestionToSectionAction $attachAction,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $section, $createAction, $attachAction, $user): void {
            $question = $createAction->handle($request->toData(), $user);
            $attachAction->handle($section, $question);
        });

        return back()->with('success', 'Question created and added to section.');
    }

    public function createInlineCoding(
        StoreCodingQuestionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        CreateCodingQuestionAction $createAction,
        AttachQuestionToSectionAction $attachAction,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $section, $createAction, $attachAction, $user): void {
            $question = $createAction->handle($request->toData(), $user);
            $attachAction->handle($section, $question);
        });

        return back()->with('success', 'Question created and added to section.');
    }

    public function createInlineRlhf(
        StoreRlhfQuestionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        CreateRlhfQuestionAction $createAction,
        AttachQuestionToSectionAction $attachAction,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $section, $createAction, $attachAction, $user): void {
            $question = $createAction->handle($request->toData(), $user);
            $attachAction->handle($section, $question);
        });

        return back()->with('success', 'Question created and added to section.');
    }
}
