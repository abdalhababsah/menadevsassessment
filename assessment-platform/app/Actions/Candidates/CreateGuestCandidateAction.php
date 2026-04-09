<?php

namespace App\Actions\Candidates;

use App\Models\Candidate;
use App\Models\CandidateEmailVerification;
use App\Notifications\SendCandidateVerificationEmail;
use Illuminate\Support\Str;

final class CreateGuestCandidateAction
{
    public function handle(string $email): Candidate
    {
        $candidate = Candidate::firstOrCreate(
            ['email' => $email],
            ['is_guest' => true],
        );

        /** @var CandidateEmailVerification $verification */
        $verification = $candidate->emailVerifications()->create([
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24),
        ]);

        $candidate->notify(new SendCandidateVerificationEmail($verification));

        return $candidate;
    }
}
