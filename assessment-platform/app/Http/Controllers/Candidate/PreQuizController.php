<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\QuizInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PreQuizController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $token = (string) $request->session()->get('quiz_invitation_token', '');
        if ($token === '') {
            return redirect('/')->withErrors(['invitation' => 'No invitation in progress.']);
        }

        $invitation = QuizInvitation::where('token', $token)->with('quiz')->first();
        if ($invitation === null || ! $invitation->isUsable()) {
            return redirect()->route('candidate.invitations.show', $token);
        }

        /** @var Candidate $candidate */
        $candidate = Auth::guard('candidate')->user();

        $quiz = $invitation->quiz;

        return Inertia::render('Candidate/PreQuiz', [
            'candidate' => [
                'name' => $candidate->name,
                'email' => $candidate->email,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_seconds' => $quiz->time_limit_seconds,
                'navigation_mode' => $quiz->navigation_mode->value,
                'camera_enabled' => $quiz->camera_enabled,
                'anti_cheat_enabled' => $quiz->anti_cheat_enabled,
                'max_fullscreen_exits' => $quiz->max_fullscreen_exits,
                'passing_score' => $quiz->passing_score === null ? null : (float) $quiz->passing_score,
            ],
            'invitation_token' => $token,
        ]);
    }
}
