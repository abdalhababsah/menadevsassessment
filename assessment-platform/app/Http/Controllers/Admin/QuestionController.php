<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Questions\CreateCodingQuestionAction;
use App\Actions\Questions\CreateMultiSelectQuestionAction;
use App\Actions\Questions\CreateSingleSelectQuestionAction;
use App\Actions\Questions\UpdateCodingQuestionAction;
use App\Actions\Questions\UpdateMultiSelectQuestionAction;
use App\Actions\Questions\UpdateSingleSelectQuestionAction;
use App\Actions\Rlhf\CreateRlhfQuestionAction;
use App\Actions\Rlhf\UpdateRlhfQuestionAction;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Questions\StoreCodingQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreMultiSelectQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreRlhfQuestionRequest;
use App\Http\Requests\Admin\Questions\StoreSingleSelectQuestionRequest;
use App\Http\Requests\Admin\Questions\UpdateCodingQuestionRequest;
use App\Http\Requests\Admin\Questions\UpdateMultiSelectQuestionRequest;
use App\Http\Requests\Admin\Questions\UpdateRlhfQuestionRequest;
use App\Http\Requests\Admin\Questions\UpdateSingleSelectQuestionRequest;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Question::class);

        $questions = QueryBuilder::for(Question::with(['tags', 'creator'])->withCount('sections'))
            ->allowedFilters(
                AllowedFilter::exact('type'),
                AllowedFilter::exact('difficulty'),
                AllowedFilter::exact('creator', 'created_by'),
                AllowedFilter::callback('tags', function (Builder $query, array|string $tagIds): void {
                    $ids = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
                    $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $ids));
                }),
                AllowedFilter::callback('q', function (Builder $query, string $term): void {
                    $query->where('stem', 'like', '%'.$term.'%');
                }),
                AllowedFilter::callback('created_after', function (Builder $query, string $date): void {
                    $query->where('created_at', '>=', $date);
                }),
                AllowedFilter::callback('created_before', function (Builder $query, string $date): void {
                    $query->where('created_at', '<=', $date);
                }),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at'),
                AllowedSort::field('difficulty'),
                AllowedSort::field('points'),
                AllowedSort::callback('most_used', function (Builder $query, bool $descending) {
                    $query->withCount('sections')->orderBy('sections_count', $descending ? 'desc' : 'asc');
                }),
            )
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 12))
            ->withQueryString()
            ->through(fn (Question $question): array => [
                'id' => $question->id,
                'stem' => Str::limit($question->stem, 120),
                'full_stem' => $question->stem,
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'difficulty' => $question->difficulty->value,
                'difficulty_label' => $question->difficulty->label(),
                'points' => $question->points,
                'tags' => $question->tags->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])->all(),
                'creator' => $question->creator ? [
                    'id' => $question->creator->id,
                    'name' => $question->creator->name,
                    'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($question->creator->name).'&background=random',
                ] : null,
                'created_at' => $question->created_at?->diffForHumans(),
                'usages_count' => $question->sections_count,
            ]);

        $tags = Tag::orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name]);

        $creators = User::whereIn('id', Question::query()->select('created_by')->distinct())
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id, 
                'name' => $user->name,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random',
            ]);

        $stats = [
            'total' => Question::count(),
            'by_type' => [
                'single_select' => Question::where('type', QuestionType::SingleSelect)->count(),
                'multi_select' => Question::where('type', QuestionType::MultiSelect)->count(),
                'coding' => Question::where('type', QuestionType::Coding)->count(),
                'rlhf' => Question::where('type', QuestionType::Rlhf)->count(),
            ],
        ];

        return Inertia::render('Admin/QuestionBank/Index', [
            'questions' => $questions,
            'tags' => $tags,
            'creators' => $creators,
            'stats' => $stats,
            'filters' => request()->query('filter', []),
            'sort' => trim(request()->query('sort', '-created_at')),
        ]);
    }

    public function apiShow(Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        $question->load(['tags', 'options', 'media', 'creator', 'codingConfig', 'testCases', 'rlhfConfig', 'rlhfCriteria', 'rlhfFormFields']);

        return response()->json([
            'question' => $this->serializeQuestionForEdit($question),
            'usages' => $question->quizzes()->get()->map(fn ($quiz) => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'published_at' => $quiz->published_at?->diffForHumans(),
            ]),
        ]);
    }

    public function duplicate(Question $question): RedirectResponse
    {
        $this->authorize('create', Question::class);

        $newQuestion = DB::transaction(function () use ($question) {
            $clone = $question->replicate(['version', 'created_at', 'updated_at', 'deleted_at']);
            $clone->stem = $clone->stem . ' (Copy)';
            $clone->version = 1;
            $clone->created_by = auth()->id();
            $clone->save();

            // Sync Tags
            $clone->tags()->sync($question->tags->pluck('id'));

            // Duplicate Options
            foreach ($question->options as $option) {
                $optionClone = $option->replicate(['question_id']);
                $optionClone->question_id = $clone->id;
                $optionClone->save();
            }

            // Duplicate Coding Config
            if ($question->codingConfig) {
                $codingClone = $question->codingConfig->replicate(['question_id']);
                $codingClone->question_id = $clone->id;
                $codingClone->save();
            }

            // Duplicate Test Cases
            foreach ($question->testCases as $tc) {
                $tcClone = $tc->replicate(['question_id']);
                $tcClone->question_id = $clone->id;
                $tcClone->save();
            }

            // Duplicate RLHF
            if ($question->rlhfConfig) {
                $rlhfClone = $question->rlhfConfig->replicate(['question_id']);
                $rlhfClone->question_id = $clone->id;
                $rlhfClone->save();
            }

            foreach ($question->rlhfCriteria as $crit) {
                $critClone = $crit->replicate(['question_id']);
                $critClone->question_id = $clone->id;
                $critClone->save();
            }

            foreach ($question->rlhfFormFields as $field) {
                $fieldClone = $field->replicate(['question_id']);
                $fieldClone->question_id = $clone->id;
                $fieldClone->save();
            }

            return $clone;
        });

        return redirect()->route('admin.questions.index')->with('success', 'Question duplicated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Question deleted.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Question::class);

        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'application/json',
            'Content-Disposition' => 'attachment; filename=questions_'.date('Y-m-d_H-i-s').'.json',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () {
            echo "[\n";
            $first = true;

            $query = QueryBuilder::for(Question::with(['tags', 'options', 'codingConfig', 'testCases', 'rlhfConfig', 'rlhfCriteria', 'rlhfFormFields']))
                ->allowedFilters(
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('difficulty'),
                    AllowedFilter::exact('creator', 'created_by'),
                    AllowedFilter::callback('tags', function (Builder $query, array|string $tagIds): void {
                        $ids = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
                        $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $ids));
                    }),
                    AllowedFilter::callback('q', function (Builder $query, string $term): void {
                        $query->where('stem', 'like', '%'.$term.'%');
                    })
                );

            $query->chunk(100, function ($questions) use (&$first) {
                foreach ($questions as $question) {
                    if (! $first) {
                        echo ",\n";
                    }
                    echo json_encode($this->serializeQuestionForEdit($question));
                    $first = false;
                }
            });

            echo "\n]";
        }, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Question::class);

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain'],
        ]);

        $content = file_get_contents($request->file('file')->path());
        $questions = json_decode($content, true);

        if (! is_array($questions)) {
            return back()->withErrors(['file' => 'Invalid JSON format.']);
        }

        DB::transaction(function () use ($questions, $request) {
            foreach ($questions as $qData) {
                // Extremely simplified import mechanism
                $question = Question::create([
                    'type' => $qData['type'],
                    'stem' => $qData['stem'],
                    'instructions' => $qData['instructions'],
                    'difficulty' => $qData['difficulty'],
                    'points' => $qData['points'] ?? collect($qData['options'] ?? [])->where('is_correct', true)->count() ?: 1,
                    'time_limit_seconds' => $qData['time_limit_seconds'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                if (! empty($qData['options'])) {
                    foreach ($qData['options'] as $idx => $opt) {
                        $question->options()->create([
                            'content' => $opt['content'],
                            'content_type' => $opt['content_type'] ?? 'text',
                            'is_correct' => $opt['is_correct'] ?? false,
                            'position' => $opt['position'] ?? $idx,
                        ]);
                    }
                }

                // Note: Coding configurations / Test Cases / RLHF are skipped in this minimal import example
                // and would require full nested schema validation logic in a production scenario.
            }
        });

        return back()->with('success', 'Questions imported successfully.');
    }

    public function create(string $type): Response
    {
        $this->authorize('create', Question::class);

        // Accept either hyphenated (URL-friendly, REST convention) or
        // underscored (enum-friendly) type slugs so both `/create/single-select`
        // and `/create/single_select` resolve to the same page.
        $normalized = str_replace('-', '_', $type);

        $page = match ($normalized) {
            'single_select' => 'Admin/QuestionBank/Create/SingleSelect',
            'multi_select' => 'Admin/QuestionBank/Create/MultiSelect',
            'coding' => 'Admin/QuestionBank/Create/Coding',
            'rlhf' => 'Admin/QuestionBank/Create/Rlhf',
            default => abort(404),
        };

        return Inertia::render($page, [
            'tags' => $this->tagOptions(),
        ]);
    }

    public function storeSingleSelect(
        StoreSingleSelectQuestionRequest $request,
        CreateSingleSelectQuestionAction $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $sectionId = $request->integer('quiz_section_id') ?: null;
        $action->handle($request->toData(), $user, $sectionId);

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function storeMultiSelect(
        StoreMultiSelectQuestionRequest $request,
        CreateMultiSelectQuestionAction $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $sectionId = $request->integer('quiz_section_id') ?: null;
        $action->handle($request->toData(), $user, $sectionId);

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function storeCoding(
        StoreCodingQuestionRequest $request,
        CreateCodingQuestionAction $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $sectionId = $request->integer('quiz_section_id') ?: null;
        $action->handle($request->toData(), $user, $sectionId);

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function edit(Question $question): Response
    {
        $this->authorize('update', $question);

        $question->load(['tags', 'options', 'codingConfig', 'testCases', 'rlhfConfig', 'rlhfCriteria', 'rlhfFormFields']);

        $page = match ($question->type) {
            QuestionType::SingleSelect => 'Admin/QuestionBank/Edit/SingleSelect',
            QuestionType::MultiSelect => 'Admin/QuestionBank/Edit/MultiSelect',
            QuestionType::Coding => 'Admin/QuestionBank/Edit/Coding',
            QuestionType::Rlhf => 'Admin/QuestionBank/Edit/Rlhf',
        };

        return Inertia::render($page, [
            'question' => $this->serializeQuestionForEdit($question),
            'tags' => $this->tagOptions(),
        ]);
    }

    public function updateSingleSelect(
        UpdateSingleSelectQuestionRequest $request,
        Question $question,
        UpdateSingleSelectQuestionAction $action,
    ): RedirectResponse {
        $action->handle(
            $question,
            $request->toData(),
            $request->boolean('force_new_version'),
            $request->boolean('force_in_place'),
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function updateMultiSelect(
        UpdateMultiSelectQuestionRequest $request,
        Question $question,
        UpdateMultiSelectQuestionAction $action,
    ): RedirectResponse {
        $action->handle(
            $question,
            $request->toData(),
            $request->boolean('force_new_version'),
            $request->boolean('force_in_place'),
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function updateCoding(
        UpdateCodingQuestionRequest $request,
        Question $question,
        UpdateCodingQuestionAction $action,
    ): RedirectResponse {
        $action->handle(
            $question,
            $request->toData(),
            $request->boolean('force_new_version'),
            $request->boolean('force_in_place'),
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function storeRlhf(
        StoreRlhfQuestionRequest $request,
        CreateRlhfQuestionAction $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $sectionId = $request->integer('quiz_section_id') ?: null;
        $action->handle($request->toData(), $user, $sectionId);

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function updateRlhf(
        UpdateRlhfQuestionRequest $request,
        Question $question,
        UpdateRlhfQuestionAction $action,
    ): RedirectResponse {
        $action->handle(
            $question,
            $request->toData(),
            $request->boolean('force_new_version'),
            $request->boolean('force_in_place'),
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function tagOptions(): array
    {
        return Tag::orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQuestionForEdit(Question $question): array
    {
        return [
            'id' => $question->id,
            'type' => $question->type->value,
            'stem' => $question->stem,
            'instructions' => $question->instructions,
            'difficulty' => $question->difficulty->value,
            'points' => (float) $question->points,
            'time_limit_seconds' => $question->time_limit_seconds,
            'version' => $question->version,
            'tags' => $question->tags->pluck('id')->all(),
            'options' => $question->options->map(fn ($option): array => [
                'content' => $option->content,
                'content_type' => $option->content_type,
                'is_correct' => $option->is_correct,
                'position' => $option->position,
            ])->all(),
            'coding_config' => $question->codingConfig ? [
                'allowed_languages' => $question->codingConfig->allowed_languages,
                'starter_code' => $question->codingConfig->starter_code,
                'time_limit_ms' => $question->codingConfig->time_limit_ms,
                'memory_limit_mb' => $question->codingConfig->memory_limit_mb,
            ] : null,
            'test_cases' => $question->testCases->map(fn ($tc): array => [
                'input' => $tc->input,
                'expected_output' => $tc->expected_output,
                'is_hidden' => $tc->is_hidden,
                'weight' => (float) $tc->weight,
            ])->all(),
            'rlhf_config' => $question->rlhfConfig ? [
                'number_of_turns' => $question->rlhfConfig->number_of_turns,
                'candidate_input_mode' => $question->rlhfConfig->candidate_input_mode,
                'model_a' => $question->rlhfConfig->model_a,
                'model_b' => $question->rlhfConfig->model_b,
                'generation_params' => $question->rlhfConfig->generation_params,
                'enable_pre_prompt_form' => $question->rlhfConfig->enable_pre_prompt_form,
                'enable_post_prompt_form' => $question->rlhfConfig->enable_post_prompt_form,
                'enable_rewrite_step' => $question->rlhfConfig->enable_rewrite_step,
                'enable_post_rewrite_form' => $question->rlhfConfig->enable_post_rewrite_form,
                'guidelines_markdown' => $question->rlhfConfig->guidelines_markdown,
            ] : null,
            'rlhf_criteria' => $question->rlhfCriteria->map(fn ($c): array => [
                'name' => $c->name,
                'description' => $c->description,
                'scale_type' => $c->scale_type->value,
                'scale_labels' => $c->scale_labels,
                'justification_required_when' => $c->justification_required_when,
                'position' => $c->position,
            ])->all(),
            'rlhf_form_fields' => $question->rlhfFormFields->map(fn ($f): array => [
                'stage' => $f->stage->value,
                'field_key' => $f->field_key,
                'label' => $f->label,
                'description' => $f->description,
                'field_type' => $f->field_type->value,
                'options' => $f->options,
                'required' => $f->required,
                'min_length' => $f->min_length,
                'position' => $f->position,
            ])->all(),
        ];
    }
}
