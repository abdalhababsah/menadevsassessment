<?php

namespace App\Actions\Rlhf;

use App\Actions\Quizzes\AttachQuestionToSectionAction;
use App\Data\Rlhf\RlhfCriterionData;
use App\Data\Rlhf\RlhfFormFieldData;
use App\Data\Rlhf\RlhfQuestionData;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuizSection;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateRlhfQuestionAction
{
    public function __construct(
        private AuditLogger $audit,
        private AttachQuestionToSectionAction $attachAction,
    ) {}

    /**
     * Create an RLHF question. When `$quizSectionId` is provided the
     * question is also attached to that section inside the same transaction.
     */
    public function handle(
        RlhfQuestionData $data,
        User $creator,
        ?int $quizSectionId = null,
    ): Question {
        return DB::transaction(function () use ($data, $creator, $quizSectionId): Question {
            $question = Question::create([
                'type' => QuestionType::Rlhf,
                'stem' => $data->stem,
                'instructions' => $data->instructions,
                'difficulty' => QuestionDifficulty::from($data->difficulty),
                'points' => $data->points,
                'time_limit_seconds' => $data->time_limit_seconds,
                'created_by' => $creator->id,
            ]);

            $question->tags()->sync($data->tags);

            $question->rlhfConfig()->create([
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
            ]);

            foreach ($data->criteria as $criterion) {
                $this->createCriterion($question, $criterion);
            }

            foreach ($data->form_fields as $field) {
                $this->createFormField($question, $field);
            }

            $this->audit->log('question.created', $question, [
                'type' => 'rlhf',
                'stem' => Str::limit($data->stem, 100),
                'turns' => $data->number_of_turns,
            ]);

            if ($quizSectionId !== null) {
                $section = QuizSection::query()->findOrFail($quizSectionId);
                $this->attachAction->handle($section, $question);
            }

            return $question;
        });
    }

    private function createCriterion(Question $question, RlhfCriterionData $criterion): void
    {
        $question->rlhfCriteria()->create([
            'name' => $criterion->name,
            'description' => $criterion->description,
            'scale_type' => $criterion->scale_type,
            'scale_labels' => $criterion->scale_labels,
            'justification_required_when' => $criterion->justification_required_when,
            'position' => $criterion->position,
        ]);
    }

    private function createFormField(Question $question, RlhfFormFieldData $field): void
    {
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
}
