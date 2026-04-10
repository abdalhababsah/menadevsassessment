<?php

namespace App\Http\Requests\Admin\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

final class StoreQuizInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invite.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'email_domain_restriction' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email_domain_restriction.regex' => 'Please enter a valid domain (e.g. example.com).',
        ];
    }
}
