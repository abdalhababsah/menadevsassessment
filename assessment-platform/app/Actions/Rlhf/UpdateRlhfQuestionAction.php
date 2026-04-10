<?php

namespace App\Actions\Rlhf;

use App\Data\Rlhf\RlhfCriterionData;
use App\Data\Rlhf\RlhfFormFieldData;
use App\Data\Rlhf\RlhfQuestionData;
use App\Enums\QuestionDifficulty;
use App\Models\Question;
use App\Services\AuditLogger;
use App\Services\QuestionBank\QuestionVersioningService;
use Illuminate\Support\Facades\DB;

final class UpdateRlhfQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
        private QuestionVersioningService $versioning,
    ) {}

    public function handle(
        Question $question,
        RlhfQuestionData $data,
        bool $forceNewVersion = false,
        bool $forceInPlace = false,
    ): Question {
        $shouldFork = $forceNewVersion
            || ($this->versioning->isUsedInQuizzes($question) && ! $forceInPlace);

        if ($shouldFork) {
            $newQuestion = $this->versioning->forkNewVersion($question, [
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
                'tags' => $data->tags,
                'rlhf_config' => [
                    'number_of_turns' => $data->number_of_turns,
                    'candidate_input_mode' => $data->candidate_input_mode,
                    'model_a' => $data->model_a,
                    'model_b' => $data->model_b,
                    'generation_params' => $data->generation_params,
                    'enable_pre_prompt_form' => $data->enable_pre_prompt_form,
                    'enable_post_prompt_form' => $data->enable_post_prompt_form,
                    'enable_rewrite_step' => $data->enable_rewrite_step,
                    'enable_post_rewrite_form' => $data->enable_post_rewrite_form,
                    'guidelines_markdown' => $data->guidelines_markdown,
                ],
                'rlhf_criteria' => array_map(
                    fn (RlhfCriterionData $c): array => [
                        'name' => $c->name,
                        'description' => $c->description,
                        'scale_type' => $c->scale_type,
                        'scale_labels' => $c->scale_labels,
                        'justification_required_when' => $c->justification_required_when,
                        'position' => $c->position,
                    ],
                    $data->criteria,
                ),
                'rlhf_form_fields' => array_map(
                    fn (RlhfFormFieldData $f): array => [
                        'stage' => $f->stage,
                        'field_key' => $f->field_key,
                        'label' => $f->label,
                        'description' => $f->description,
                        'field_type' => $f->field_type,
                        'options' => $f->options,
                        'required' => $f->required,
                        'min_length' => $f->min_length,
                        'position' => $f->position,
                    ],
                    $data->form_fields,
                ),
            ]);

            $this->audit->log('question.versioned', $newQuestion, [
                'parent_id' => $question->id,
                'version' => $newQuestion->version,
            ]);

            return $newQuestion;
        }

        return DB::transaction(function () use ($question, $data): Question {
            $question->update([
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
            ]);

            $question->tags()->sync($data->tags);

            $question->rlhfConfig()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'number_of_turns' => $data->number_of_turns,
                    'candidate_input_mode' => $data->candidate_input_mode,
                    'model_a' => $data->model_a,
                    'model_b' => $data->model_b,
                    'generation_params' => $data->generation_params,
                    'enable_pre_prompt_form' => $data->enable_pre_prompt_form,
                    'enable_post_prompt_form' => $data->enable_post_prompt_form,
                    'enable_rewrite_step' => $data->enable_rewrite_step,
                    'enable_post_rewrite_form' => $data->enable_post_rewrite_form,
                    'guidelines_markdown' => $data->guidelines_markdown,
                ],
            );

            $question->rlhfCriteria()->delete();
            foreach ($data->criteria as $criterion) {
                $question->rlhfCriteria()->create([
                    'name' => $criterion->name,
                    'description' => $criterion->description,
                    'scale_type' => $criterion->scale_type,
                    'scale_labels' => $criterion->scale_labels,
                    'justification_required_when' => $criterion->justification_required_when,
                    'position' => $criterion->position,
                ]);
            }

            $question->rlhfFormFields()->delete();
            foreach ($data->form_fields as $field) {
                $question->rlhfFormFields()->create([
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

            $this->audit->log('question.updated', $question, ['type' => 'rlhf']);

            return $question->refresh();
        });
    }
}
