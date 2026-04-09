<?php

namespace App\Actions\Candidates;

use App\Models\Candidate;

final class RegisterCandidateAction
{
    public function handle(string $name, string $email, string $password): Candidate
    {
        return Candidate::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_guest' => false,
            'email_verified_at' => now(),
        ]);
    }
}
