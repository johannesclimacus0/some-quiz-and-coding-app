<?php

namespace App\Actions\Admin\Groups;

use App\Data\Admin\Groups\CreateGroupData;
use App\Models\Group;

final class CreateGroupAction
{
    public function handle(CreateGroupData $data): Group
    {
        return Group::query()->create($data->toArray());
    }
}
