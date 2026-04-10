<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\QuizInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        /** @var QuizInvitation|null $invitation */
        $invitation = QuizInvitation::query()
            ->where('token', $token)
            ->with('quiz')
            ->first();

        if ($invitation === null) {
            return $this->renderError('not_found', 'This invitation link is invalid.');
        }

        if (! $invitation->isUsable()) {
            return $this->renderError($invitation->status()->value, $this->statusMessage($invitation->status()));
        }

        // Stash the invitation token in the session so the candidate auth flow
        // can pick it up after sign-up / verification.
        $request->session()->put('quiz_invitation_token', $token);

        // Already authenticated as a verified candidate? Skip straight to pre-quiz.
        /** @var Candidate|null $candidate */
        $candidate = Auth::guard('candidate')->user();
        if ($candidate !== null && $candidate->hasVerifiedEmail()) {
            return redirect()->route('candidate.pre-quiz');
        }

        return Inertia::render('Candidate/Invitation/EmailEntry', [
            'invitation' => [
                'token' => $invitation->token,
                'quiz' => [
                    'id' => $invitation->quiz->id,
                    'title' => $invitation->quiz->title,
                    'description' => $invitation->quiz->description,
                ],
                'email_domain_restriction' => $invitation->email_domain_restriction,
            ],
        ]);
    }

    private function renderError(string $reason, string $message): Response
    {
        return Inertia::render('Candidate/InvitationError', [
            'reason' => $reason,
            'message' => $message,
        ]);
    }

    private function statusMessage(InvitationStatus $status): string
    {
        return match ($status) {
            InvitationStatus::Expired => 'This invitation link has expired.',
            InvitationStatus::Exhausted => 'This invitation link has reached its usage limit.',
            InvitationStatus::Revoked => 'This invitation link has been revoked.',
            InvitationStatus::Active => 'This invitation is active.',
        };
    }
}
