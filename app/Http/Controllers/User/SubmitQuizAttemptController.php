<?php

namespace App\Http\Controllers\User;

use App\Actions\QuizAttempts\SubmitQuizAttemptAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\QuizAttemptResource;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubmitQuizAttemptController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz, SubmitQuizAttemptAction $action): QuizAttemptResource
    {
        Gate::authorize('participate', $quiz);

        return new QuizAttemptResource($action->handle($request->user(), $quiz));
    }
}
