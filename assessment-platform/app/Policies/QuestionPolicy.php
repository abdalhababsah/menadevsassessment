<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

final class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('questionbank.view');
    }

    public function view(User $user, Question $question): bool
    {
        return $user->hasPermissionTo('questionbank.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('questionbank.create');
    }

    public function update(User $user, Question $question): bool
    {
        return $user->hasPermissionTo('questionbank.edit');
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->hasPermissionTo('questionbank.delete');
    }
}
