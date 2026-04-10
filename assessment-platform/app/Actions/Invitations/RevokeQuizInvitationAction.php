<?php

namespace App\Actions\Invitations;

use App\Models\QuizInvitation;
use App\Services\AuditLogger;

final class RevokeQuizInvitationAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(QuizInvitation $invitation): QuizInvitation
    {
        $invitation->update(['revoked_at' => now()]);

        $this->audit->log('quiz.invitation_revoked', $invitation, [
            'quiz_id' => $invitation->quiz_id,
            'token_preview' => substr($invitation->token, 0, 8).'...',
        ]);

        return $invitation->refresh();
    }
}
