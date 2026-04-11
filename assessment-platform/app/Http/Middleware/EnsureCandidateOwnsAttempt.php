<?php

namespace App\Http\Middleware;

use App\Models\Candidate;
use App\Models\QuizAttempt;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCandidateOwnsAttempt
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $attemptId = (int) $request->session()->get('quiz_attempt_id', 0);

        if ($attemptId < 1) {
            return $this->missingAttemptResponse($request);
        }

        /** @var Candidate|null $candidate */
        $candidate = Auth::guard('candidate')->user();

        if ($candidate === null) {
            return $this->missingAttemptResponse($request);
        }

        $attempt = QuizAttempt::query()->find($attemptId);

        if ($attempt === null) {
            $request->session()->forget('quiz_attempt_id');

            return $this->missingAttemptResponse($request);
        }

        if ($attempt->candidate_id !== $candidate->id) {
            abort(403, 'You are not allowed to access this quiz attempt.');
        }

        if (! $attempt->isInProgress()) {
            return $this->staleAttemptResponse($request);
        }

        $request->attributes->set('quizAttempt', $attempt);

        return $next($request);
    }

    private function missingAttemptResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No active quiz attempt was found for this session.',
            ], 404);
        }

        return redirect()->route('candidate.pre-quiz')
            ->withErrors(['quiz' => 'Start your assessment before opening the runner.']);
    }

    private function staleAttemptResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This quiz attempt is no longer in progress.',
                'redirect' => route('candidate.quiz.submitted'),
            ], 409);
        }

        return redirect()->route('candidate.quiz.submitted');
    }
}
