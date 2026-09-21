<?php

namespace App\Exceptions\QuizAttempts;

final class GradeVersionConflict extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Ответ уже был оценён другим пользователем. Обновите страницу.');
    }
}
