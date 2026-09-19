<?php

namespace App\Actions\Admin\Groups;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RemoveUserFromGroupAction
{
    public function handle(Group $group, User $user): void
    {
        DB::transaction(function () use ($group, $user): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
            $lockedGroup->users()->detach($user->getKey());
        });
    }
}
