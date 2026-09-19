<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Answers\SetCorrectAnswerAction;
use App\Data\Admin\Answers\SetCorrectAnswerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetCorrectAnswerRequest;
use App\Http\Resources\Admin\AnswerResource;
use App\Models\Question;
use App\Models\Quiz;

class SetCorrectAnswerController extends Controller
{
    public function __invoke(SetCorrectAnswerRequest $request, Quiz $quiz, Question $question, SetCorrectAnswerAction $action): AnswerResource
    {
        $answer = $action->handle($quiz, $question, SetCorrectAnswerData::fromArray($request->validated()));

        return new AnswerResource($answer);
    }
}
