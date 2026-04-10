<?php

namespace App\Http\Requests\Admin\Questions;

use App\Data\Questions\CodingQuestionData;
use App\Data\Questions\CodingTestCaseData;
use App\Enums\QuestionDifficulty;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCodingQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Question::class);
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
        ];
    }

    public function toData(): CodingQuestionData
    {
        $validated = $this->validated();

        return CodingQuestionData::from([
            ...$validated,
            'tags' => $validated['tags'] ?? [],
            'starter_code' => $validated['starter_code'] ?? null,
            'test_cases' => array_map(
                fn (array $tc): CodingTestCaseData => CodingTestCaseData::from($tc),
                $validated['test_cases'],
            ),
        ]);
    }
}
