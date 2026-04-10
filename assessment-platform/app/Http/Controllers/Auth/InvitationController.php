<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Users\AcceptInvitationAction;
use App\Exceptions\VerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = UserInvitation::where('token', $token)->with('user')->first();

        if (! $invitation || $invitation->isExpired() || $invitation->isConsumed()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This invitation link is invalid or has expired.',
            ]);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->user->email,
            'name' => $invitation->user->name,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token, AcceptInvitationAction $action): RedirectResponse
    {
        try {
            $user = $action->handle($token, $request->validated('password'));
        } catch (VerificationException $e) {
            return redirect()->route('login')->withErrors([
                'email' => $e->getMessage(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
