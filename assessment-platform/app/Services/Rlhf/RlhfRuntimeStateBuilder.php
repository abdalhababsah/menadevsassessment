<?php

namespace App\Services\Rlhf;

use App\Enums\RlhfFormStage;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfTurn;
use App\Models\RlhfQuestionFormField;
use Illuminate\Support\Collection;

final class RlhfRuntimeStateBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(AttemptAnswer $answer): array
    {
        $answer->loadMissing([
            'attempt.quiz',
            'question.rlhfConfig',
            'question.rlhfCriteria',
            'question.rlhfFormFields',
            'rlhfTurns.generationJobs',
            'rlhfTurns.evaluations',
            'rlhfTurns.formResponses',
        ]);

        $question = $answer->question;
        $config = $question->rlhfConfig;
        $turns = $answer->rlhfTurns->sortBy('turn_number')->values();
        $currentTurn = $this->currentTurn($answer);

        return [
            'quiz' => [
                'id' => $answer->attempt->quiz->id,
                'title' => $answer->attempt->quiz->title,
            ],
            'question' => [
                'id' => $question->id,
                'stem' => $question->stem,
                'instructions' => $question->instructions,
                'guidelines_markdown' => $config?->guidelines_markdown,
                'number_of_turns' => $config?->number_of_turns,
                'candidate_input_mode' => $config?->candidate_input_mode,
                'enable_pre_prompt_form' => $config?->enable_pre_prompt_form ?? false,
                'enable_post_prompt_form' => $config?->enable_post_prompt_form ?? false,
                'enable_rewrite_step' => $config?->enable_rewrite_step ?? false,
                'enable_post_rewrite_form' => $config?->enable_post_rewrite_form ?? false,
                'criteria' => $question->rlhfCriteria->map(fn ($criterion) => [
                    'id' => $criterion->id,
                    'name' => $criterion->name,
                    'description' => $criterion->description,
                    'scale_type' => $criterion->scale_type->value,
                    'scale_labels' => $criterion->scale_labels,
                    'justification_required_when' => $criterion->justification_required_when,
                    'position' => $criterion->position,
                ])->values(),
                'form_fields' => [
                    RlhfFormStage::PrePrompt->value => $this->serializeFormFields($question->rlhfFormFields, RlhfFormStage::PrePrompt),
                    RlhfFormStage::PostPrompt->value => $this->serializeFormFields($question->rlhfFormFields, RlhfFormStage::PostPrompt),
                    RlhfFormStage::PostRewrite->value => $this->serializeFormFields($question->rlhfFormFields, RlhfFormStage::PostRewrite),
                ],
            ],
            'turns' => $turns->map(fn (AttemptRlhfTurn $turn) => $this->serializeTurn($answer, $turn))->values(),
            'current_turn' => $currentTurn ? $this->serializeTurn($answer, $currentTurn) : null,
            'current_step' => $this->currentStep($answer, $currentTurn),
            'progress' => [
                'current_turn' => $currentTurn?->turn_number,
                'completed_turns' => $turns->whereNotNull('completed_at')->count(),
                'total_turns' => $config?->number_of_turns ?? 0,
            ],
            'question_completed' => $answer->status->value === 'answered',
        ];
    }

    public function currentTurn(AttemptAnswer $answer): ?AttemptRlhfTurn
    {
        $answer->loadMissing('rlhfTurns');

        /** @var AttemptRlhfTurn|null $currentTurn */
        $currentTurn = $answer->rlhfTurns
            ->whereNull('completed_at')
            ->sortBy('turn_number')
            ->first();

        return $currentTurn;
    }

    public function currentStep(AttemptAnswer $answer, ?AttemptRlhfTurn $turn): string
    {
        $answer->loadMissing([
            'question.rlhfConfig',
            'question.rlhfCriteria',
            'question.rlhfFormFields',
        ]);

        if ($answer->status->value === 'answered') {
            return 'completed';
        }

        if ($turn === null) {
            return 'turn_complete';
        }

        $config = $answer->question->rlhfConfig;

        if (($config?->enable_pre_prompt_form ?? false) && ! $this->stageIsComplete($answer, $turn, RlhfFormStage::PrePrompt)) {
            return 'pre_prompt_form';
        }

        if ($turn->candidate_input === null || $turn->candidate_input === '') {
            return 'prompt_input';
        }

        if (! $turn->bothResponsesReady()) {
            return 'response_pair';
        }

        if (($config?->enable_post_prompt_form ?? false) && ! $this->stageIsComplete($answer, $turn, RlhfFormStage::PostPrompt)) {
            return 'post_prompt_form';
        }

        if (! $this->sideEvaluationsComplete($answer, $turn, 'a')) {
            return 'evaluate_a';
        }

        if (! $this->sideEvaluationsComplete($answer, $turn, 'b')) {
            return 'evaluate_b';
        }

        if ($turn->sxs_rating === null) {
            return 'sxs_rating';
        }

        if (($config?->enable_rewrite_step ?? false) && $turn->selected_side !== null && $turn->selected_response_rewrite === null) {
            return 'rewrite';
        }

        if (($config?->enable_post_rewrite_form ?? false) && ! $this->stageIsComplete($answer, $turn, RlhfFormStage::PostRewrite)) {
            return 'post_rewrite_form';
        }

        return 'turn_complete';
    }

    private function stageIsComplete(AttemptAnswer $answer, AttemptRlhfTurn $turn, RlhfFormStage $stage): bool
    {
        $fields = $answer->question->rlhfFormFields
            ->where('stage', $stage)
            ->sortBy('position')
            ->values();

        if ($fields->isEmpty()) {
            return true;
        }

        $responses = $turn->formResponses
            ->where('stage', $stage)
            ->keyBy('field_key');

        foreach ($fields as $field) {
            if (! $field->required) {
                continue;
            }

            $response = $responses->get($field->field_key);

            if ($response === null || trim((string) $response->value) === '') {
                return false;
            }
        }

        return true;
    }

    private function sideEvaluationsComplete(AttemptAnswer $answer, AttemptRlhfTurn $turn, string $side): bool
    {
        $criteriaCount = $answer->question->rlhfCriteria->count();

        if ($criteriaCount === 0) {
            return true;
        }

        return $turn->evaluations
            ->where('response_side', $side)
            ->count() >= $criteriaCount;
    }

    /**
     * @param  Collection<int, RlhfQuestionFormField>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function serializeFormFields($fields, RlhfFormStage $stage): array
    {
        return $fields
            ->where('stage', $stage)
            ->sortBy('position')
            ->map(fn (RlhfQuestionFormField $field) => [
                'id' => $field->id,
                'field_key' => $field->field_key,
                'label' => $field->label,
                'description' => $field->description,
                'field_type' => $field->field_type->value,
                'options' => $field->options,
                'required' => $field->required,
                'min_length' => $field->min_length,
                'position' => $field->position,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTurn(AttemptAnswer $answer, AttemptRlhfTurn $turn): array
    {
        return [
            'id' => $turn->id,
            'turn_number' => $turn->turn_number,
            'candidate_input' => $turn->candidate_input,
            'candidate_input_audio_url' => $turn->candidate_input_audio_url,
            'response_a' => $turn->response_a,
            'response_b' => $turn->response_b,
            'model_a' => $turn->model_a,
            'model_b' => $turn->model_b,
            'generation_status' => $turn->generation_status->value,
            'generation_error' => $turn->generation_error,
            'generated_at' => optional($turn->generated_at)->toIso8601String(),
            'sxs_rating' => $turn->sxs_rating,
            'sxs_justification' => $turn->sxs_justification,
            'selected_side' => $turn->selected_side?->value,
            'selected_response_rewrite' => $turn->selected_response_rewrite,
            'rewrite_completed_at' => optional($turn->rewrite_completed_at)->toIso8601String(),
            'completed_at' => optional($turn->completed_at)->toIso8601String(),
            'form_responses' => [
                RlhfFormStage::PrePrompt->value => $this->serializeFormResponses($turn, RlhfFormStage::PrePrompt),
                RlhfFormStage::PostPrompt->value => $this->serializeFormResponses($turn, RlhfFormStage::PostPrompt),
                RlhfFormStage::PostRewrite->value => $this->serializeFormResponses($turn, RlhfFormStage::PostRewrite),
            ],
            'evaluations' => [
                'a' => $this->serializeEvaluations($turn, 'a'),
                'b' => $this->serializeEvaluations($turn, 'b'),
            ],
            'generation' => [
                'a' => $this->serializeGenerationSide($turn, 'a'),
                'b' => $this->serializeGenerationSide($turn, 'b'),
            ],
            'counters' => [
                'pre_prompt' => $this->completionCounter($answer, $turn, RlhfFormStage::PrePrompt),
                'post_prompt' => $this->completionCounter($answer, $turn, RlhfFormStage::PostPrompt),
                'post_rewrite' => $this->completionCounter($answer, $turn, RlhfFormStage::PostRewrite),
                'evaluate_a' => [
                    'completed' => $turn->evaluations->where('response_side', 'a')->count(),
                    'total' => $answer->question->rlhfCriteria->count(),
                ],
                'evaluate_b' => [
                    'completed' => $turn->evaluations->where('response_side', 'b')->count(),
                    'total' => $answer->question->rlhfCriteria->count(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGenerationSide(AttemptRlhfTurn $turn, string $side): array
    {
        $job = $turn->generationJobs->firstWhere('side', $side);
        $response = $side === 'a' ? $turn->response_a : $turn->response_b;

        return [
            'status' => $response !== null
                ? 'ready'
                : ($job?->status?->value ?? $turn->generation_status->value),
            'error' => $job?->last_error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completionCounter(AttemptAnswer $answer, AttemptRlhfTurn $turn, RlhfFormStage $stage): array
    {
        $fields = $answer->question->rlhfFormFields->where('stage', $stage);
        $responses = $turn->formResponses->where('stage', $stage);

        return [
            'completed' => $responses->count(),
            'total' => $fields->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFormResponses(AttemptRlhfTurn $turn, RlhfFormStage $stage): array
    {
        return $turn->formResponses
            ->where('stage', $stage)
            ->mapWithKeys(fn ($response) => [$response->field_key => $this->parseStoredValue((string) $response->value)])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function serializeEvaluations(AttemptRlhfTurn $turn, string $side): array
    {
        return $turn->evaluations
            ->where('response_side', $side)
            ->mapWithKeys(fn ($evaluation) => [
                (string) $evaluation->criterion_id => [
                    'criterion_id' => $evaluation->criterion_id,
                    'rating_value' => $evaluation->rating_value,
                    'justification' => $evaluation->justification,
                ],
            ])
            ->all();
    }

    private function parseStoredValue(string $value): mixed
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
