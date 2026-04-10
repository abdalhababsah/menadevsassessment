<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

final class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('quiz.view');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quiz.create');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.edit');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.delete');
    }

    public function publish(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.publish');
    }

    public function duplicate(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.duplicate');
    }
}
