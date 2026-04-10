<?php

namespace App\Http\Requests\Candidate;

use App\Models\QuizInvitation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:candidates,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $token = (string) $this->session()->get('quiz_invitation_token', '');
                if ($token === '') {
                    return;
                }

                $invitation = QuizInvitation::where('token', $token)->first();
                if ($invitation === null || $invitation->email_domain_restriction === null) {
                    return;
                }

                $email = (string) $this->input('email');
                $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
                $required = strtolower($invitation->email_domain_restriction);

                if ($domain !== $required) {
                    $validator->errors()->add('email', "Only @{$required} email addresses are allowed for this assessment.");
                }
            },
        ];
    }
}
