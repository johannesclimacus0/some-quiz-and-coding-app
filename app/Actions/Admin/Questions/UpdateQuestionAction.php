<?php

namespace App\Actions\Admin\Questions;

use App\Data\Admin\Questions\UpdateQuestionData;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class UpdateQuestionAction
{
    public function handle(Quiz $quiz, Question $question, UpdateQuestionData $data): Question
    {
        return DB::transaction(function () use ($quiz, $question, $data) {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuestion = $lockedQuiz->questions()
                ->lockForUpdate()
                ->findOrFail($question->getKey());

            $lockedQuestion->update($data->toArray());

            if ($lockedQuestion->wasChanged(['text', 'position'])) {
                $lockedQuiz->increment('content_version');
            }

            return $lockedQuestion;
        });
    }
}
