<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Answers\CreateAnswerAction;
use App\Actions\Admin\Answers\DeleteAnswerAction;
use App\Actions\Admin\Answers\UpdateAnswerAction;
use App\Data\Admin\Answers\CreateAnswerData;
use App\Data\Admin\Answers\UpdateAnswerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnswerRequest;
use App\Http\Requests\Admin\UpdateAnswerRequest;
use App\Http\Resources\Admin\AnswerResource;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AnswerController extends Controller
{
    public function index(Quiz $quiz, Question $question): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Answer::class);

        return AnswerResource::collection($question->answers()->paginate(20));
    }

    public function store(StoreAnswerRequest $request, Quiz $quiz, Question $question, CreateAnswerAction $action): AnswerResource
    {
        $answer = $action->handle($quiz, $question, CreateAnswerData::fromArray($request->validated()));

        return new AnswerResource($answer->refresh());
    }

    public function show(Quiz $quiz, Question $question, Answer $answer): AnswerResource
    {
        Gate::authorize('view', $answer);

        return new AnswerResource($answer);
    }

    public function update(UpdateAnswerRequest $request, Quiz $quiz, Question $question, Answer $answer, UpdateAnswerAction $action): AnswerResource
    {
        $answer = $action->handle($quiz, $question, $answer, UpdateAnswerData::fromArray($request->validated()));

        return new AnswerResource($answer);
    }

    public function destroy(Quiz $quiz, Question $question, Answer $answer, DeleteAnswerAction $action): Response
    {
        Gate::authorize('delete', $answer);
        $action->handle($quiz, $question, $answer);

        return response()->noContent();
    }
}
