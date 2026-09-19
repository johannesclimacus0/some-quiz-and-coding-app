<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Quizzes\CreateQuizAction;
use App\Actions\Admin\Quizzes\DeleteQuizAction;
use App\Actions\Admin\Quizzes\UpdateQuizAction;
use App\Data\Admin\Quizzes\CreateQuizData;
use App\Data\Admin\Quizzes\UpdateQuizData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuizRequest;
use App\Http\Requests\Admin\UpdateQuizRequest;
use App\Http\Resources\Admin\QuizResource;
use App\Models\Quiz;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Quiz::class);

        return QuizResource::collection(Quiz::query()->latest('id')->paginate(18));
    }

    public function store(StoreQuizRequest $request, CreateQuizAction $action): QuizResource
    {
        $quiz = $action->handle(CreateQuizData::fromArray($request->validated()));

        return new QuizResource($quiz->refresh());
    }

    public function show(Quiz $quiz): QuizResource
    {
        Gate::authorize('view', $quiz);

        return new QuizResource($quiz->load('questions.answers'));
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz, UpdateQuizAction $action): QuizResource
    {
        $quiz = $action->handle($quiz, UpdateQuizData::fromArray($request->validated()));

        return new QuizResource($quiz);
    }

    public function destroy(Quiz $quiz, DeleteQuizAction $action): Response
    {
        Gate::authorize('delete', $quiz);
        $action->handle($quiz);

        return response()->noContent();
    }
}
