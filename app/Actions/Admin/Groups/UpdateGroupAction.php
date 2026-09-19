<?php

namespace App\Actions\Admin\Groups;

use App\Data\Admin\Groups\UpdateGroupData;
use App\Models\Group;

final class UpdateGroupAction
{
    public function handle(Group $group, UpdateGroupData $data): Group
    {
        $group->update($data->toArray());

        return $group;
    }
}
