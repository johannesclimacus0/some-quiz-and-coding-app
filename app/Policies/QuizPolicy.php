<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class QuizPolicy
{
    public function participate(User $user, Quiz $quiz): bool
    {
        return $quiz->groups()
            ->whereHas('users', fn (Builder $query) => $query->whereKey($user->getKey()))
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin();
    }
}
