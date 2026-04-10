<?php

namespace App\Http\Requests\Admin\Questions;

use App\Data\Rlhf\RlhfCriterionData;
use App\Data\Rlhf\RlhfFormFieldData;
use App\Data\Rlhf\RlhfQuestionData;
use App\Enums\QuestionDifficulty;
use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use App\Enums\RlhfScaleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateRlhfQuestionRequest extends FormRequest
{
    private const SCALE_LENGTHS = [
        'three_point_quality' => 3,
        'five_point_centered' => 5,
        'five_point_satisfaction' => 5,
    ];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('question'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stem' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'difficulty' => ['required', Rule::in(QuestionDifficulty::values())],
            'points' => ['required', 'numeric', 'min:0'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:1'],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:tags,id'],

            'number_of_turns' => ['required', 'integer', 'between:1,10'],
            'candidate_input_mode' => ['required', Rule::in(['text', 'voice', 'both'])],
            'model_a' => ['required', 'string', 'max:255'],
            'model_b' => ['required', 'string', 'max:255'],
            'generation_params' => ['nullable', 'array'],
            'enable_pre_prompt_form' => ['required', 'boolean'],
            'enable_post_prompt_form' => ['required', 'boolean'],
            'enable_rewrite_step' => ['required', 'boolean'],
            'enable_post_rewrite_form' => ['required', 'boolean'],
            'guidelines_markdown' => ['nullable', 'string'],

            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['required', 'string'],
            'criteria.*.scale_type' => ['required', Rule::in(RlhfScaleType::values())],
            'criteria.*.scale_labels' => ['required', 'array'],
            'criteria.*.justification_required_when' => ['array'],
            'criteria.*.position' => ['required', 'integer', 'min:0'],

            'form_fields' => ['array'],
            'form_fields.*.stage' => ['required', Rule::in(RlhfFormStage::values())],
            'form_fields.*.field_key' => ['required', 'string', 'max:255'],
            'form_fields.*.label' => ['required', 'string', 'max:255'],
            'form_fields.*.description' => ['nullable', 'string'],
            'form_fields.*.field_type' => ['required', Rule::in(RlhfFieldType::values())],
            'form_fields.*.options' => ['nullable', 'array'],
            'form_fields.*.required' => ['required', 'boolean'],
            'form_fields.*.min_length' => ['nullable', 'integer', 'min:0'],
            'form_fields.*.position' => ['required', 'integer', 'min:0'],

            'force_new_version' => ['nullable', 'boolean'],
            'force_in_place' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var array<int, array<string, mixed>> $criteria */
                $criteria = (array) $this->input('criteria', []);

                foreach ($criteria as $index => $criterion) {
                    $scaleType = (string) ($criterion['scale_type'] ?? '');
                    $expectedLength = self::SCALE_LENGTHS[$scaleType] ?? null;

                    if ($expectedLength === null) {
                        continue;
                    }

                    $labels = (array) ($criterion['scale_labels'] ?? []);
                    if (count($labels) !== $expectedLength) {
                        $validator->errors()->add(
                            "criteria.{$index}.scale_labels",
                            "Scale type '{$scaleType}' requires exactly {$expectedLength} labels.",
                        );
                    }
                }
            },
        ];
    }

    public function toData(): RlhfQuestionData
    {
        $validated = $this->validated();

        return RlhfQuestionData::from([
            'stem' => $validated['stem'],
            'instructions' => $validated['instructions'] ?? null,
            'difficulty' => $validated['difficulty'],
            'points' => $validated['points'],
            'time_limit_seconds' => $validated['time_limit_seconds'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'number_of_turns' => $validated['number_of_turns'],
            'candidate_input_mode' => $validated['candidate_input_mode'],
            'model_a' => $validated['model_a'],
            'model_b' => $validated['model_b'],
            'generation_params' => $validated['generation_params'] ?? null,
            'enable_pre_prompt_form' => $validated['enable_pre_prompt_form'],
            'enable_post_prompt_form' => $validated['enable_post_prompt_form'],
            'enable_rewrite_step' => $validated['enable_rewrite_step'],
            'enable_post_rewrite_form' => $validated['enable_post_rewrite_form'],
            'guidelines_markdown' => $validated['guidelines_markdown'] ?? null,
            'criteria' => array_map(
                fn (array $c): RlhfCriterionData => RlhfCriterionData::from([
                    ...$c,
                    'justification_required_when' => $c['justification_required_when'] ?? [],
                ]),
                $validated['criteria'],
            ),
            'form_fields' => array_map(
                fn (array $f): RlhfFormFieldData => RlhfFormFieldData::from([
                    ...$f,
                    'description' => $f['description'] ?? null,
                    'options' => $f['options'] ?? null,
                    'min_length' => $f['min_length'] ?? null,
                ]),
                $validated['form_fields'] ?? [],
            ),
        ]);
    }
}
