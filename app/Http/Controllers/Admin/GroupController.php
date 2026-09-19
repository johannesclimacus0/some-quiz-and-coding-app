<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Groups\CreateGroupAction;
use App\Actions\Admin\Groups\DeleteGroupAction;
use App\Actions\Admin\Groups\UpdateGroupAction;
use App\Data\Admin\Groups\CreateGroupData;
use App\Data\Admin\Groups\UpdateGroupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Http\Resources\Admin\GroupResource;
use App\Models\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Group::class);

        return GroupResource::collection(
            Group::query()->withCount(['users', 'quizzes'])->latest('id')->paginate(18)
        );
    }

    public function store(StoreGroupRequest $request, CreateGroupAction $action): GroupResource
    {
        $group = $action->handle(CreateGroupData::fromArray($request->validated()));

        return new GroupResource($group->loadCount(['users', 'quizzes']));
    }

    public function show(Group $group): GroupResource
    {
        Gate::authorize('view', $group);

        return new GroupResource($group->loadCount(['users', 'quizzes']));
    }

    public function update(UpdateGroupRequest $request, Group $group, UpdateGroupAction $action): GroupResource
    {
        $group = $action->handle($group, UpdateGroupData::fromArray($request->validated()));

        return new GroupResource($group->loadCount(['users', 'quizzes']));
    }

    public function destroy(Group $group, DeleteGroupAction $action): Response
    {
        Gate::authorize('delete', $group);
        $action->handle($group);

        return response()->noContent();
    }
}
