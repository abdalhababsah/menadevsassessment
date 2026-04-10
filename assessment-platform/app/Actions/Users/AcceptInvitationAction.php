<?php

namespace App\Actions\Users;

use App\Exceptions\VerificationException;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;

final class AcceptInvitationAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(string $token, string $password): User
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (! $invitation) {
            throw VerificationException::invalidToken();
        }

        if ($invitation->isConsumed()) {
            throw VerificationException::alreadyVerified();
        }

        if ($invitation->isExpired()) {
            throw VerificationException::expired();
        }

        /** @var User $user */
        $user = $invitation->user;
        $user->update([
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $invitation->update(['consumed_at' => now()]);

        $this->audit->log('user.invitation_accepted', $user, [
            'email' => $user->email,
        ]);

        return $user;
    }
}
