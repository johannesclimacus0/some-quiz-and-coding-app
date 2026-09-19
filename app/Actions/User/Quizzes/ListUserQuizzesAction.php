<?php

namespace App\Actions\User\Quizzes;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ListUserQuizzesAction
{
    public function handle(User $user): LengthAwarePaginator
    {
        $userId = $user->getKey();

        return Quiz::query()
            ->whereHas('groups.users', fn (Builder $query) => $query->whereKey($userId))
            ->with(['attempts' => fn (HasMany $query) => $query->where('user_id', $userId)->with('answers')])
            ->latest('id')
            ->paginate(18);
    }
}
