<?php

namespace App\Actions\Admin\Groups;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddUserToGroupAction
{
    public function handle(Group $group, User $user): void
    {
        DB::transaction(function () use ($group, $user): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->role !== UserRole::User) {
                throw ValidationException::withMessages([
                    'user' => ['Администратора нельзя добавить в группу'],
                ]);
            }

            $lockedGroup->users()->syncWithoutDetaching([$lockedUser->getKey()]);
        });
    }
}
