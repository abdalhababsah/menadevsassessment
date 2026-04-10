<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Quizzes\CreateQuizSectionAction;
use App\Actions\Quizzes\DeleteQuizSectionAction;
use App\Actions\Quizzes\ReorderQuizSectionsAction;
use App\Actions\Quizzes\UpdateQuizSectionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quizzes\ReorderQuizSectionsRequest;
use App\Http\Requests\Admin\Quizzes\StoreQuizSectionRequest;
use App\Http\Requests\Admin\Quizzes\UpdateQuizSectionRequest;
use App\Models\Quiz;
use App\Models\QuizSection;
use Illuminate\Http\RedirectResponse;

class QuizSectionController extends Controller
{
    public function store(StoreQuizSectionRequest $request, Quiz $quiz, CreateQuizSectionAction $action): RedirectResponse
    {
        $action->handle(
            $quiz,
            $request->validated('title'),
            $request->validated('description'),
            $request->validated('time_limit_seconds'),
        );

        return back()->with('success', 'Section added.');
    }

    public function update(
        UpdateQuizSectionRequest $request,
        Quiz $quiz,
        QuizSection $section,
        UpdateQuizSectionAction $action,
    ): RedirectResponse {
        abort_unless($section->quiz_id === $quiz->id, 404);

        $action->handle(
            $section,
            $request->validated('title'),
            $request->validated('description'),
            $request->validated('time_limit_seconds'),
        );

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Quiz $quiz, QuizSection $section, DeleteQuizSectionAction $action): RedirectResponse
    {
        $this->authorize('update', $quiz);
        abort_unless($section->quiz_id === $quiz->id, 404);

        $action->handle($section);

        return back()->with('success', 'Section removed.');
    }

    public function reorder(
        ReorderQuizSectionsRequest $request,
        Quiz $quiz,
        ReorderQuizSectionsAction $action,
    ): RedirectResponse {
        $action->handle($quiz, $request->validated('section_ids'));

        return back()->with('success', 'Sections reordered.');
    }
}
