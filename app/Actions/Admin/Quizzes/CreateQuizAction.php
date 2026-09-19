<?php

namespace App\Actions\Admin\Quizzes;

use App\Data\Admin\Quizzes\CreateQuizData;
use App\Models\Quiz;

final class CreateQuizAction
{
    public function handle(CreateQuizData $data): Quiz
    {
        return Quiz::query()->create($data->toArray());
    }
}
