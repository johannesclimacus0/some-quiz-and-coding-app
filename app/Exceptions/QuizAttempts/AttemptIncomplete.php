<?php

namespace App\Exceptions\QuizAttempts;

final class AttemptIncomplete extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Ответьте на все вопросы перед завершением');
    }
}
