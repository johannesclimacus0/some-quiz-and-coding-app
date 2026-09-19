<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Questions\CreateQuestionAction;
use App\Actions\Admin\Questions\DeleteQuestionAction;
use App\Actions\Admin\Questions\UpdateQuestionAction;
use App\Data\Admin\Questions\CreateQuestionData;
use App\Data\Admin\Questions\UpdateQuestionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Http\Resources\Admin\QuestionResource;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function index(Quiz $quiz): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Question::class);

        return QuestionResource::collection($quiz->questions()->paginate(20));
    }

    public function store(StoreQuestionRequest $request, Quiz $quiz, CreateQuestionAction $action): QuestionResource
    {
        $question = $action->handle($quiz, CreateQuestionData::fromArray($request->validated()));

        return new QuestionResource($question->refresh());
    }

    public function show(Quiz $quiz, Question $question): QuestionResource
    {
        Gate::authorize('view', $question);

        return new QuestionResource($question->load('answers'));
    }

    public function update(UpdateQuestionRequest $request, Quiz $quiz, Question $question, UpdateQuestionAction $action): QuestionResource
    {
        $question = $action->handle($quiz, $question, UpdateQuestionData::fromArray($request->validated()));

        return new QuestionResource($question);
    }

    public function destroy(Quiz $quiz, Question $question, DeleteQuestionAction $action): Response
    {
        Gate::authorize('delete', $question);
        $action->handle($quiz, $question);

        return response()->noContent();
    }
}
