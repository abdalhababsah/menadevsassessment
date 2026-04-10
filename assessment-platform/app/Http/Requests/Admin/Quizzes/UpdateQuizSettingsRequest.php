<?php

namespace App\Http\Requests\Admin\Quizzes;

use App\Enums\QuizNavigationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateQuizSettingsRequest extends FormRequest
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
            'passing_score' => ['nullable', 'numeric', 'between:0,100'],
            'randomize_questions' => ['required', 'boolean'],
            'randomize_options' => ['required', 'boolean'],
            'navigation_mode' => ['required', Rule::in(QuizNavigationMode::values())],
            'camera_enabled' => ['required', 'boolean'],
            'anti_cheat_enabled' => ['required', 'boolean'],
            'max_fullscreen_exits' => ['required', 'integer', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
