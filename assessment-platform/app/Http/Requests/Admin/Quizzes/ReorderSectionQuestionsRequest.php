<?php

namespace App\Http\Requests\Admin\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderSectionQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('quiz'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_question_ids' => ['required', 'array', 'min:1'],
            'section_question_ids.*' => ['integer', 'exists:quiz_section_questions,id'],
        ];
    }
}
