<?php

namespace App\Actions\Candidates;

use App\Exceptions\VerificationException;
use App\Models\Candidate;
use App\Models\CandidateEmailVerification;

final class VerifyCandidateEmailAction
{
    public function handle(string $token): Candidate
    {
        $verification = CandidateEmailVerification::where('token', $token)->first();

        if (! $verification) {
            throw VerificationException::invalidToken();
        }

        if ($verification->isConsumed()) {
            throw VerificationException::alreadyVerified();
        }

        if ($verification->isExpired()) {
            throw VerificationException::expired();
        }

        $verification->markConsumed();

        /** @var Candidate $candidate */
        $candidate = $verification->candidate;
        $candidate->markEmailAsVerified();

        return $candidate;
    }
}
