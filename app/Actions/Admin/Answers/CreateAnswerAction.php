<?php

namespace App\Actions\Admin\Answers;

use App\Data\Admin\Answers\CreateAnswerData;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class CreateAnswerAction
{
    public function handle(Quiz $quiz, Question $question, CreateAnswerData $data): Answer
    {
        return DB::transaction(function () use ($quiz, $question, $data): Answer {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $answer = $lockedQuestion->answers()->create($data->toArray());

            $lockedQuiz->increment('content_version');

            return $answer;
        });
    }
}
