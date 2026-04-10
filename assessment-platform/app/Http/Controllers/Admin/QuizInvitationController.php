<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Invitations\CreateQuizInvitationAction;
use App\Actions\Invitations\RevokeQuizInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quizzes\StoreQuizInvitationRequest;
use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuizInvitationController extends Controller
{
    public function index(Quiz $quiz): Response
    {
        $this->authorize('update', $quiz);

        if (! $this->actor()->can('invite.view')) {
            abort(403);
        }

        $invitations = $quiz->invitations()
            ->latest()
            ->get()
            ->map(fn (QuizInvitation $invitation): array => $this->serializeInvitation($invitation));

        return Inertia::render('Admin/Quizzes/Edit/Invitations', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $quiz->status->value,
            ],
            'invitations' => $invitations,
        ]);
    }

    public function store(
        StoreQuizInvitationRequest $request,
        Quiz $quiz,
        CreateQuizInvitationAction $action,
    ): RedirectResponse {
        $this->authorize('update', $quiz);

        $action->handle(
            $quiz,
            $this->actor(),
            $request->validated('max_uses'),
            $request->validated('expires_at'),
            $request->validated('email_domain_restriction'),
        );

        return back()->with('success', 'Invitation created.');
    }

    public function destroy(
        Quiz $quiz,
        QuizInvitation $invitation,
        RevokeQuizInvitationAction $action,
    ): RedirectResponse {
        $this->authorize('update', $quiz);
        abort_unless($invitation->quiz_id === $quiz->id, 404);

        if (! $this->actor()->can('invite.revoke')) {
            abort(403);
        }

        $action->handle($invitation);

        return back()->with('success', 'Invitation revoked.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvitation(QuizInvitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'token' => $invitation->token,
            'public_url' => url("/i/{$invitation->token}"),
            'max_uses' => $invitation->max_uses,
            'uses_count' => $invitation->uses_count,
            'expires_at' => $invitation->expires_at?->toDateTimeString(),
            'email_domain_restriction' => $invitation->email_domain_restriction,
            'revoked_at' => $invitation->revoked_at?->toDateTimeString(),
            'created_at' => $invitation->created_at?->toDateTimeString(),
            'status' => $invitation->status()->value,
            'status_label' => $invitation->status()->label(),
            'is_usable' => $invitation->isUsable(),
        ];
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }
}
