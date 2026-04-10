<?php

namespace App\Http\Requests\Admin\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateQuizSectionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
