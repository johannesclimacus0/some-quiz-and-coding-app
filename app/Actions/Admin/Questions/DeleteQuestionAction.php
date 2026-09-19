<?php

namespace App\Actions\Admin\Questions;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class DeleteQuestionAction
{
    public function handle(Quiz $quiz, Question $question): void
    {
        DB::transaction(function () use ($quiz, $question) {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $lockedQuestion->delete();

            $lockedQuiz->increment('content_version');

        });
    }
}
