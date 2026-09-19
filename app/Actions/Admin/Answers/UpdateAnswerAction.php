<?php

namespace App\Actions\Admin\Answers;

use App\Data\Admin\Answers\UpdateAnswerData;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class UpdateAnswerAction
{
    public function handle(Quiz $quiz, Question $question, Answer $answer, UpdateAnswerData $data): Answer
    {
        return DB::transaction(function () use ($quiz, $question, $answer, $data): Answer {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $lockedAnswer = $lockedQuestion->answers()
                ->lockForUpdate()
                ->findOrFail($answer->getKey());

            $lockedAnswer->update($data->toArray());

            if ($lockedAnswer->wasChanged(['text', 'position'])) {
                $lockedQuiz->increment('content_version');
            }

            return $lockedAnswer;
        });
    }
}
