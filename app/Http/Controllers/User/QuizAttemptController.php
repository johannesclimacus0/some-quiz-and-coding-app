<?php

namespace App\Http\Controllers\User;

use App\Actions\QuizAttempts\GetQuizAttemptAction;
use App\Actions\QuizAttempts\StartQuizAttemptAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\QuizAttemptResource;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizAttemptController extends Controller
{
    public function show(Request $request, Quiz $quiz, GetQuizAttemptAction $action): QuizAttemptResource
    {
        Gate::authorize('participate', $quiz);

        return new QuizAttemptResource($action->handle($request->user(), $quiz));
    }

    public function store(Request $request, Quiz $quiz, StartQuizAttemptAction $action): JsonResponse
    {
        Gate::authorize('participate', $quiz);
        $attempt = $action->handle($request->user(), $quiz);
        $status = $attempt->wasRecentlyCreated ? 201 : 200;

        return new QuizAttemptResource($attempt)->response()->setStatusCode($status);
    }
}
