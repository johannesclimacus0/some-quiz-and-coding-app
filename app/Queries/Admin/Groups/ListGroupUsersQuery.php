<?php

namespace App\Queries\Admin\Groups;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListGroupUsersQuery
{
    public function paginate(Group $group, string $search): LengthAwarePaginator
    {
        return User::query()
            ->where('role', UserRole::User)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")
            ))
            ->withExists(['groups as assigned' => fn (Builder $query) => $query->whereKey($group->getKey())])
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();
    }
}
