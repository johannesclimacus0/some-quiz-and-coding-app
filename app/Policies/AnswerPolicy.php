<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;

class AnswerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Answer $answer): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Answer $answer): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Answer $answer): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Answer $answer): bool
    {
        return $user->isAdmin();
    }
}
