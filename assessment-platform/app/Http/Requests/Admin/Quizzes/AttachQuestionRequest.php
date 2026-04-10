<?php

namespace App\Http\Requests\Admin\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

final class AttachQuestionRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'points_override' => ['nullable', 'numeric', 'min:0'],
            'time_limit_override_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
