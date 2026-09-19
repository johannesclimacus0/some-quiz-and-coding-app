<?php

namespace App\Exceptions\QuizAttempts;

final class AttemptAlreadySubmitted extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Попытка уже завершена');
    }
}
