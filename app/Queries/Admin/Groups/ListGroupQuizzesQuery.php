<?php

namespace App\Queries\Admin\Groups;

use App\Models\Group;
use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListGroupQuizzesQuery
{
    public function paginate(Group $group, string $search): LengthAwarePaginator
    {
        return Quiz::query()
            ->when($search !== '', fn (Builder $query) => $query->whereLike('title', "%{$search}%"))
            ->withExists(['groups as assigned' => fn (Builder $query) => $query->whereKey($group->getKey())])
            ->latest('id')
            ->paginate(18)
            ->withQueryString();
    }
}
