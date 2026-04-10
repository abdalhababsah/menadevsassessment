<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class InviteUserAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, string>  $roles
     */
    public function handle(string $name, string $email, array $roles): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'is_active' => false,
        ]);

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        /** @var UserInvitation $invitation */
        $invitation = $user->invitations()->create([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $user->notify(new UserInvitationNotification($invitation));

        $this->audit->log('user.invited', $user, [
            'name' => $name,
            'email' => $email,
            'roles' => $roles,
        ]);

        return $user;
    }
}
