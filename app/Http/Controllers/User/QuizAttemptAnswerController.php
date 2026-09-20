<?php

namespace App\Http\Controllers\User;

use App\Actions\QuizAttempts\SaveQuizAttemptAnswerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveQuizAttemptAnswerRequest;
use App\Http\Resources\User\QuizAttemptResource;
use App\Models\Quiz;

class QuizAttemptAnswerController extends Controller
{
    public function __invoke(
        SaveQuizAttemptAnswerRequest $request,
        Quiz $quiz,
        string $questionUuid,
        SaveQuizAttemptAnswerAction $action
    ): QuizAttemptResource {
        $attempt = $action->handle(
            $request->user(),
            $quiz,
            $questionUuid,
            $request->validated('response')
        );

        return new QuizAttemptResource($attempt);
    }
}
