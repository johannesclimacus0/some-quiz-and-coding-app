<?php

namespace App\Actions\Admin\Answers;

use App\Data\Admin\Answers\SetCorrectAnswerData;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class SetCorrectAnswerAction
{
    public function handle(Quiz $quiz, Question $question, SetCorrectAnswerData $data): Answer
    {
        return DB::transaction(function () use ($quiz, $question, $data): Answer {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $answer = $lockedQuestion->answers()
                ->lockForUpdate()
                ->where('uuid', $data->answerUuid)
                ->firstOrFail();

            $hasAnotherCorrectAnswer = $lockedQuestion->answers()
                ->where('is_correct', true)
                ->where('id', '!=', $answer->getKey())
                ->exists();

            if ($answer->is_correct && !$hasAnotherCorrectAnswer) {
                return $answer;
            }

            $lockedQuestion->answers()
                ->where('is_correct', true)
                ->update(['is_correct' => false]);

            $answer->update(['is_correct' => true]);
            $lockedQuiz->increment('content_version');

            return $answer;
        });
    }
}
