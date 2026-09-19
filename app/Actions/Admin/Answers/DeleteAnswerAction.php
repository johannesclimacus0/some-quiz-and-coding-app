<?php

namespace App\Actions\Admin\Answers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class DeleteAnswerAction
{
    public function handle(Quiz $quiz, Question $question, Answer $answer): void
    {
        DB::transaction(function () use ($quiz, $question, $answer): void {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $lockedAnswer = $lockedQuestion->answers()
                ->lockForUpdate()
                ->findOrFail($answer->getKey());

            $lockedAnswer->delete();

            $lockedQuiz->increment('content_version');
        });
    }
}
