<?php

namespace App\Actions\Admin\Quizzes;

use App\Data\Admin\Quizzes\UpdateQuizData;
use App\Models\Quiz;

final class UpdateQuizAction
{
    public function handle(Quiz $quiz, UpdateQuizData $data): Quiz
    {
        $quiz->update($data->toArray());

        return $quiz;
    }
}
