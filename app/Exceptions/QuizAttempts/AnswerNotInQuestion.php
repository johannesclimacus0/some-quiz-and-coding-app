<?php

namespace App\Exceptions\QuizAttempts;

final class AnswerNotInQuestion extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Ответ не относится к этому вопросу');
    }
}
