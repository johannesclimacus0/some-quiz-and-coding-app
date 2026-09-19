<?php

namespace App\Exceptions\QuizAttempts;

final class QuestionNotInAttempt extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Вопрос не входит в эту попытку');
    }
}
