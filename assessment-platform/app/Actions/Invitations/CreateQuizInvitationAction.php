<?php

namespace App\Actions\Invitations;

use App\Models\Quiz;
use App\Models\QuizInvitation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Str;

final class CreateQuizInvitationAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(
        Quiz $quiz,
        User $creator,
        ?int $maxUses = null,
        ?string $expiresAt = null,
        ?string $emailDomainRestriction = null,
    ): QuizInvitation {
        /** @var QuizInvitation $invitation */
        $invitation = $quiz->invitations()->create([
            'token' => $this->generateUniqueToken(),
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
            'email_domain_restriction' => $emailDomainRestriction,
            'created_by' => $creator->id,
        ]);

        $this->audit->log('quiz.invitation_created', $invitation, [
            'quiz_id' => $quiz->id,
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
            'email_domain_restriction' => $emailDomainRestriction,
        ]);

        return $invitation;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (QuizInvitation::where('token', $token)->exists());

        return $token;
    }
}
