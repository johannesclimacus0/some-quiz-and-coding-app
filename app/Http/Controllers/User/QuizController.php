<?php

namespace App\Http\Controllers\User;

use App\Actions\User\Quizzes\ListUserQuizzesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\QuizDetailsResource;
use App\Http\Resources\User\QuizListResource;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function index(Request $request, ListUserQuizzesAction $action): AnonymousResourceCollection
    {
        return QuizListResource::collection($action->handle($request->user()));
    }

    public function show(Request $request, Quiz $quiz): QuizDetailsResource
    {
        Gate::authorize('participate', $quiz);
        $attempt = $quiz->attempts()->whereBelongsTo($request->user())->with('answers')->first();
        $attempt?->setRelation('quiz', $quiz);
        $quiz->loadCount('questions')->setAttribute('attempt', $attempt);

        return new QuizDetailsResource($quiz);
    }
}
