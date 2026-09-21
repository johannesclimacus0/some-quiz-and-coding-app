<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\QuizAttempts\GradeQuizAttemptAnswerAction;
use App\Data\Admin\QuizAttempts\GradeQuizAttemptAnswerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeQuizAttemptAnswerRequest;
use App\Http\Resources\Admin\QuizAttemptDetailResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Gate;

class GradeQuizAttemptAnswerController extends Controller
{
    public function __invoke(
        GradeQuizAttemptAnswerRequest $request,
        Quiz $quiz,
        QuizAttempt $attempt,
        string $questionUuid,
        GradeQuizAttemptAnswerAction $action,
    ): QuizAttemptDetailResource {
        Gate::authorize('view', $quiz);

        return new QuizAttemptDetailResource($action->handle(
            $request->user(),
            $quiz,
            $attempt,
            $questionUuid,
            GradeQuizAttemptAnswerData::fromArray($request->validated()),
        ));
    }
}
