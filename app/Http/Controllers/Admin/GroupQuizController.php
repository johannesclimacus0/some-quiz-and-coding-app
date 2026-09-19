<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Groups\AssignQuizToGroupAction;
use App\Actions\Admin\Groups\ListGroupQuizzesAction;
use App\Actions\Admin\Groups\UnassignQuizFromGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListGroupAssignmentsRequest;
use App\Http\Resources\Admin\GroupQuizResource;
use App\Models\Group;
use App\Models\Quiz;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class GroupQuizController extends Controller
{
    public function index(
        ListGroupAssignmentsRequest $request,
        Group $group,
        ListGroupQuizzesAction $action,
    ): AnonymousResourceCollection {
        $search = trim($request->validated('search') ?? '');

        return GroupQuizResource::collection($action->handle($group, $search));
    }

    public function store(Group $group, Quiz $quiz, AssignQuizToGroupAction $action): GroupQuizResource
    {
        Gate::authorize('update', $group);
        $action->handle($group, $quiz);

        return new GroupQuizResource($quiz->setAttribute('assigned', true));
    }

    public function destroy(Group $group, Quiz $quiz, UnassignQuizFromGroupAction $action): Response
    {
        Gate::authorize('update', $group);
        $action->handle($group, $quiz);

        return response()->noContent();
    }
}
