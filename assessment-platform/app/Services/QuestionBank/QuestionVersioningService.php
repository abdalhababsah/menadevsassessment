<?php

namespace App\Services\QuestionBank;

use App\Models\Question;
use App\Models\QuizSectionQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class QuestionVersioningService
{
    /**
     * Fork a question into a new version, copying all related rows from the
     * original and applying the supplied changes on top.
     *
     * Recognised keys in $changes:
     *  - stem, instructions, difficulty, points, time_limit_seconds (scalar overrides)
     *  - tags: array<int> of tag ids
     *  - options: array of option arrays (single/multi-select replacement)
     *  - coding_config: array (coding config replacement)
     *  - test_cases: array of test case arrays (coding test cases replacement)
     *  - rlhf_config: array (rlhf config replacement)
     *  - rlhf_criteria: array of criterion arrays (rlhf criteria replacement)
     *  - rlhf_form_fields: array of field arrays (rlhf form fields replacement)
     *
     * Any relation key not present in $changes is copied verbatim from the original.
     *
     * @param  array<string, mixed>  $changes
     */
    public function forkNewVersion(Question $original, array $changes): Question
    {
        return DB::transaction(function () use ($original, $changes): Question {
            $original->loadMissing([
                'tags',
                'options',
                'media',
                'codingConfig',
                'testCases',
                'rlhfConfig',
                'rlhfCriteria',
                'rlhfFormFields',
            ]);

            $newQuestion = Question::create([
                'type' => $original->type,
                'stem' => $changes['stem'] ?? $original->stem,
                'instructions' => array_key_exists('instructions', $changes)
                    ? $changes['instructions']
                    : $original->instructions,
                'difficulty' => $changes['difficulty'] ?? $original->difficulty,
                'points' => $changes['points'] ?? $original->points,
                'time_limit_seconds' => array_key_exists('time_limit_seconds', $changes)
                    ? $changes['time_limit_seconds']
                    : $original->time_limit_seconds,
                'version' => $original->version + 1,
                'parent_question_id' => $original->id,
                'created_by' => $original->created_by,
            ]);

            $tagIds = $changes['tags'] ?? $original->tags->pluck('id')->all();
            $newQuestion->tags()->sync($tagIds);

            $this->syncOptions($newQuestion, $original, $changes);
            $this->copyMedia($newQuestion, $original);
            $this->syncCodingConfig($newQuestion, $original, $changes);
            $this->syncTestCases($newQuestion, $original, $changes);
            $this->syncRlhfConfig($newQuestion, $original, $changes);
            $this->syncRlhfCriteria($newQuestion, $original, $changes);
            $this->syncRlhfFormFields($newQuestion, $original, $changes);

            return $newQuestion->refresh();
        });
    }

    public function isUsedInQuizzes(Question $question): bool
    {
        return QuizSectionQuestion::where('question_id', $question->id)->exists();
    }

    /**
     * @return Collection<int, QuizSectionQuestion>
     */
    public function usagesIn(Question $question): Collection
    {
        return QuizSectionQuestion::where('question_id', $question->id)
            ->with('section.quiz')
            ->get();
    }

    public function latestVersionOf(Question $question): Question
    {
        $root = $this->findRoot($question);

        $candidates = $this->collectVersionTree($root);

        return $candidates->sort(function (Question $a, Question $b): int {
            if ($a->version !== $b->version) {
                return $b->version <=> $a->version;
            }

            return ((int) $b->id) <=> ((int) $a->id);
        })->first() ?? $question;
    }

    private function findRoot(Question $question): Question
    {
        $current = $question;
        while ($current->parent_question_id !== null) {
            $parent = Question::find($current->parent_question_id);
            if ($parent === null) {
                break;
            }
            $current = $parent;
        }

        return $current;
    }

    /**
     * @return Collection<int, Question>
     */
    private function collectVersionTree(Question $root): Collection
    {
        $collected = collect([$root]);
        $children = Question::where('parent_question_id', $root->id)->get();

        foreach ($children as $child) {
            $collected = $collected->merge($this->collectVersionTree($child));
        }

        return $collected;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncOptions(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('options', $changes)) {
            foreach ((array) $changes['options'] as $option) {
                $newQuestion->options()->create((array) $option);
            }

            return;
        }

        foreach ($original->options as $option) {
            $newQuestion->options()->create([
                'content' => $option->content,
                'content_type' => $option->content_type,
                'is_correct' => $option->is_correct,
                'position' => $option->position,
            ]);
        }
    }

    private function copyMedia(Question $newQuestion, Question $original): void
    {
        foreach ($original->media as $media) {
            $newQuestion->media()->create([
                'media_type' => $media->media_type,
                'url' => $media->url,
                'position' => $media->position,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncCodingConfig(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('coding_config', $changes) && $changes['coding_config'] !== null) {
            $newQuestion->codingConfig()->create((array) $changes['coding_config']);

            return;
        }

        if ($original->codingConfig === null) {
            return;
        }

        $newQuestion->codingConfig()->create([
            'allowed_languages' => $original->codingConfig->allowed_languages,
            'starter_code' => $original->codingConfig->starter_code,
            'time_limit_ms' => $original->codingConfig->time_limit_ms,
            'memory_limit_mb' => $original->codingConfig->memory_limit_mb,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncTestCases(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('test_cases', $changes)) {
            foreach ((array) $changes['test_cases'] as $tc) {
                $newQuestion->testCases()->create((array) $tc);
            }

            return;
        }

        foreach ($original->testCases as $testCase) {
            $newQuestion->testCases()->create([
                'input' => $testCase->input,
                'expected_output' => $testCase->expected_output,
                'is_hidden' => $testCase->is_hidden,
                'weight' => $testCase->weight,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncRlhfConfig(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('rlhf_config', $changes) && $changes['rlhf_config'] !== null) {
            $newQuestion->rlhfConfig()->create((array) $changes['rlhf_config']);

            return;
        }

        if ($original->rlhfConfig === null) {
            return;
        }

        $newQuestion->rlhfConfig()->create([
            'number_of_turns' => $original->rlhfConfig->number_of_turns,
            'candidate_input_mode' => $original->rlhfConfig->candidate_input_mode,
            'model_a' => $original->rlhfConfig->model_a,
            'model_b' => $original->rlhfConfig->model_b,
            'generation_params' => $original->rlhfConfig->generation_params,
            'enable_pre_prompt_form' => $original->rlhfConfig->enable_pre_prompt_form,
            'enable_post_prompt_form' => $original->rlhfConfig->enable_post_prompt_form,
            'enable_rewrite_step' => $original->rlhfConfig->enable_rewrite_step,
            'enable_post_rewrite_form' => $original->rlhfConfig->enable_post_rewrite_form,
            'guidelines_markdown' => $original->rlhfConfig->guidelines_markdown,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncRlhfCriteria(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('rlhf_criteria', $changes)) {
            foreach ((array) $changes['rlhf_criteria'] as $criterion) {
                $newQuestion->rlhfCriteria()->create((array) $criterion);
            }

            return;
        }

        foreach ($original->rlhfCriteria as $criterion) {
            $newQuestion->rlhfCriteria()->create([
                'name' => $criterion->name,
                'description' => $criterion->description,
                'scale_type' => $criterion->scale_type,
                'scale_labels' => $criterion->scale_labels,
                'justification_required_when' => $criterion->justification_required_when,
                'position' => $criterion->position,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function syncRlhfFormFields(Question $newQuestion, Question $original, array $changes): void
    {
        if (array_key_exists('rlhf_form_fields', $changes)) {
            foreach ((array) $changes['rlhf_form_fields'] as $field) {
                $newQuestion->rlhfFormFields()->create((array) $field);
            }

            return;
        }

        foreach ($original->rlhfFormFields as $field) {
            $newQuestion->rlhfFormFields()->create([
                'stage' => $field->stage,
                'field_key' => $field->field_key,
                'label' => $field->label,
                'description' => $field->description,
                'field_type' => $field->field_type,
                'options' => $field->options,
                'required' => $field->required,
                'min_length' => $field->min_length,
                'position' => $field->position,
            ]);
        }
    }
}
