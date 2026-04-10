<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;

final class DeactivateUserAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(User $user): User
    {
        $user->update(['is_active' => false]);

        $this->audit->log('user.deactivated', $user, [
            'email' => $user->email,
        ]);

        return $user->refresh();
    }
}
