<?php

namespace App\Http\Controllers\Candidate;

use App\Actions\Candidates\CreateGuestCandidateAction;
use App\Actions\Candidates\RegisterCandidateAction;
use App\Actions\Candidates\VerifyCandidateEmailAction;
use App\Exceptions\VerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\RegisterCandidateRequest;
use App\Http\Requests\Candidate\StoreGuestCandidateRequest;
use App\Http\Requests\Candidate\VerifyEmailRequest;
use App\Models\QuizInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function submitEmail(StoreGuestCandidateRequest $request, CreateGuestCandidateAction $action): RedirectResponse
    {
        $token = (string) $request->session()->get('quiz_invitation_token', '');
        $invitation = QuizInvitation::where('token', $token)->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return redirect()->route('candidate.invitations.show', $token)
                ->withErrors(['email' => 'Your invitation link is no longer valid.']);
        }

        $email = (string) $request->validated('email');
        $candidate = $action->handle($email);

        return redirect()->route('candidate.check-email')
            ->with('check_email', $candidate->email);
    }

    public function showCheckEmail(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('check_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('candidate.invitations.show', $request->session()->get('quiz_invitation_token', ''));
        }

        // Re-flash so a refresh of this page still shows it.
        $request->session()->keep(['check_email', 'quiz_invitation_token']);

        return Inertia::render('Candidate/Invitation/CheckEmail', [
            'email' => $email,
        ]);
    }

    public function verify(VerifyEmailRequest $request, VerifyCandidateEmailAction $action): RedirectResponse
    {
        try {
            $candidate = $action->handle($request->validated('token'));
        } catch (VerificationException $e) {
            return redirect()->route('candidate.invitations.show', $request->session()->get('quiz_invitation_token', ''))
                ->withErrors(['email' => $e->getMessage()]);
        }

        Auth::guard('candidate')->login($candidate);

        return redirect()->route('candidate.pre-quiz');
    }

    public function showRegister(Request $request): Response|RedirectResponse
    {
        $token = (string) $request->session()->get('quiz_invitation_token', '');
        $invitation = QuizInvitation::where('token', $token)->with('quiz')->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return redirect()->route('candidate.invitations.show', $token);
        }

        return Inertia::render('Candidate/Invitation/Register', [
            'invitation' => [
                'token' => $invitation->token,
                'quiz' => [
                    'title' => $invitation->quiz->title,
                ],
                'email_domain_restriction' => $invitation->email_domain_restriction,
            ],
        ]);
    }

    public function register(RegisterCandidateRequest $request, RegisterCandidateAction $action): RedirectResponse
    {
        $candidate = $action->handle(
            (string) $request->validated('name'),
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        Auth::guard('candidate')->login($candidate);

        return redirect()->route('candidate.pre-quiz');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('candidate')->logout();
        $request->session()->forget(['quiz_invitation_token', 'check_email']);

        return redirect('/');
    }
}
