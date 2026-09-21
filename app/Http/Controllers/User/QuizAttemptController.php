<?php

namespace App\Http\Controllers\User;

use App\Actions\QuizAttempts\StartQuizAttemptAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\QuizAttemptResource;
use App\Models\Quiz;
use App\Queries\User\QuizAttempts\GetQuizAttemptQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizAttemptController extends Controller
{
    public function show(Request $request, Quiz $quiz, GetQuizAttemptQuery $query): QuizAttemptResource
    {
        Gate::authorize('participate', $quiz);

        return new QuizAttemptResource($query->firstOrFail($request->user(), $quiz));
    }

    public function store(Request $request, Quiz $quiz, StartQuizAttemptAction $action): JsonResponse
    {
        Gate::authorize('participate', $quiz);
        $attempt = $action->handle($request->user(), $quiz);
        $status = $attempt->wasRecentlyCreated ? 201 : 200;

        return new QuizAttemptResource($attempt)->response()->setStatusCode($status);
    }
}
