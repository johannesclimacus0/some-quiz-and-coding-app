<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\QuizDetailsResource;
use App\Http\Resources\User\QuizListResource;
use App\Models\Quiz;
use App\Queries\User\Quizzes\ListUserQuizzesQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function index(Request $request, ListUserQuizzesQuery $query): AnonymousResourceCollection
    {
        return QuizListResource::collection($query->paginate($request->user()));
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
