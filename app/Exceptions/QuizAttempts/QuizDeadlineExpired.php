<?php

namespace App\Exceptions\QuizAttempts;

final class QuizDeadlineExpired extends QuizAttemptException
{
    public function __construct()
    {
        parent::__construct('Срок прохождения квиза истёк');
    }
}
