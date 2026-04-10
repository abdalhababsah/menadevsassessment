<?php

namespace App\Http\Requests\Admin\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderQuizSectionsRequest extends FormRequest
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
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'exists:quiz_sections,id'],
        ];
    }
}
