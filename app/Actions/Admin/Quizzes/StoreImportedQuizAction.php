<?php

namespace App\Actions\Admin\Quizzes;

use App\Data\Imports\ImportQuizData;
use App\Models\Quiz;

final class StoreImportedQuizAction
{
    public function handle(ImportQuizData $data): Quiz
    {
        $quiz = Quiz::query()->create($data->toArray());

        foreach ($data->questions as $questionData) {
            $question = $quiz->questions()->create($questionData->toArray());

            foreach ($questionData->answers as $answerData) {
                $question->answers()->create($answerData->toArray());
            }
        }

        return $quiz->load('questions.answers');
    }
}
