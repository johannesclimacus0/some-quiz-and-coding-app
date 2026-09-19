<?php

namespace App\Actions\Admin\Groups;

use App\Models\Group;
use Illuminate\Support\Facades\DB;

final class DeleteGroupAction
{
    public function handle(Group $group): void
    {
        DB::transaction(function () use ($group): void {
            $group->delete();
        });
    }
}
