<?php

namespace App\Exceptions\QuizAttempts;

final class QuizNotReady extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Квиз должен содержать хотя бы один вопрос');
    }
}
