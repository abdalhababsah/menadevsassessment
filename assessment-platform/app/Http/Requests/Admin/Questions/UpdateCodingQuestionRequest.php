<?php

namespace App\Http\Requests\Admin\Questions;

use App\Data\Questions\CodingQuestionData;
use App\Data\Questions\CodingTestCaseData;
use App\Enums\QuestionDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCodingQuestionRequest extends FormRequest
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
            'allowed_languages' => ['required', 'array', 'min:1'],
            'allowed_languages.*' => ['required', 'string'],
            'starter_code' => ['nullable', 'array'],
            'time_limit_ms' => ['required', 'integer', 'min:100'],
            'memory_limit_mb' => ['required', 'integer', 'min:16'],
            'test_cases' => ['required', 'array', 'min:1'],
            'test_cases.*.input' => ['required', 'string'],
            'test_cases.*.expected_output' => ['required', 'string'],
            'test_cases.*.is_hidden' => ['required', 'boolean'],
            'test_cases.*.weight' => ['required', 'numeric', 'min:0'],
            'force_new_version' => ['nullable', 'boolean'],
            'force_in_place' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): CodingQuestionData
    {
        $validated = $this->validated();

        return CodingQuestionData::from([
            'stem' => $validated['stem'],
            'instructions' => $validated['instructions'] ?? null,
            'difficulty' => $validated['difficulty'],
            'points' => $validated['points'],
            'time_limit_seconds' => $validated['time_limit_seconds'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'allowed_languages' => $validated['allowed_languages'],
            'starter_code' => $validated['starter_code'] ?? null,
            'time_limit_ms' => $validated['time_limit_ms'],
            'memory_limit_mb' => $validated['memory_limit_mb'],
            'test_cases' => array_map(
                fn (array $tc): CodingTestCaseData => CodingTestCaseData::from($tc),
                $validated['test_cases'],
            ),
        ]);
    }
}
