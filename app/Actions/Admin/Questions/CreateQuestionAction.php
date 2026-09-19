<?php

namespace App\Actions\Admin\Questions;

use App\Data\Admin\Questions\CreateQuestionData;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class CreateQuestionAction
{
    public function handle(Quiz $quiz, CreateQuestionData $data): Question
    {
        return DB::transaction(function () use ($quiz, $data) {
            $lockedQuiz = Quiz::query()
                ->lockForUpdate()
                ->findOrFail($quiz->getKey());

            $lockedQuiz->increment('content_version');

            return $lockedQuiz->questions()->create($data->toArray());
        });
    }
}
