<?php

namespace App\Http\Requests\Admin\Quizzes;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Quiz::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
