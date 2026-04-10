<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;

final class ReactivateUserAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(User $user): User
    {
        $user->update(['is_active' => true]);

        $this->audit->log('user.reactivated', $user, [
            'email' => $user->email,
        ]);

        return $user->refresh();
    }
}
