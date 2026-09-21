<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Groups\AddUserToGroupAction;
use App\Actions\Admin\Groups\RemoveUserFromGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListGroupAssignmentsRequest;
use App\Http\Resources\Admin\GroupUserResource;
use App\Models\Group;
use App\Models\User;
use App\Queries\Admin\Groups\ListGroupUsersQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class GroupUserController extends Controller
{
    public function index(
        ListGroupAssignmentsRequest $request,
        Group $group,
        ListGroupUsersQuery $query
    ): AnonymousResourceCollection {
        $search = trim($request->validated('search') ?? '');

        return GroupUserResource::collection($query->paginate($group, $search));
    }

    public function store(Group $group, User $user, AddUserToGroupAction $action): GroupUserResource
    {
        Gate::authorize('update', $group);
        $action->handle($group, $user);

        return new GroupUserResource($user->setAttribute('assigned', true));
    }

    public function destroy(Group $group, User $user, RemoveUserFromGroupAction $action): Response
    {
        Gate::authorize('update', $group);
        $action->handle($group, $user);

        return response()->noContent();
    }
}
