<?php

namespace App\Actions\Admin\Groups;

use App\Models\Group;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class AssignQuizToGroupAction
{
    public function handle(Group $group, Quiz $quiz): void
    {
        DB::transaction(function () use ($group, $quiz): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());

            $lockedGroup->quizzes()->syncWithoutDetaching([$lockedQuiz->getKey()]);
        });
    }
}
