<?php

namespace App\Http\Requests\Admin\Questions;

use App\Data\Questions\MultiSelectQuestionData;
use App\Data\Questions\QuestionOptionData;
use App\Enums\QuestionDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateMultiSelectQuestionRequest extends FormRequest
{
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
            'options' => ['required', 'array', 'min:2'],
            'options.*.content' => ['required', 'string'],
            'options.*.content_type' => ['required', Rule::in(['text', 'image', 'audio'])],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.position' => ['required', 'integer', 'min:0'],
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
                /** @var array<int, array<string, mixed>> $options */
                $options = (array) $this->input('options', []);
                $correctCount = 0;
                foreach ($options as $option) {
                    if (! empty($option['is_correct'])) {
                        $correctCount++;
                    }
                }

                if ($correctCount < 1) {
                    $validator->errors()->add('options', 'Multi-select questions must have at least 1 correct option.');
                }
            },
        ];
    }

    public function toData(): MultiSelectQuestionData
    {
        $validated = $this->validated();

        return MultiSelectQuestionData::from([
            'stem' => $validated['stem'],
            'instructions' => $validated['instructions'] ?? null,
            'difficulty' => $validated['difficulty'],
            'points' => $validated['points'],
            'time_limit_seconds' => $validated['time_limit_seconds'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'options' => array_map(
                fn (array $opt): QuestionOptionData => QuestionOptionData::from($opt),
                $validated['options'],
            ),
        ]);
    }
}
