<?php

namespace App\Exceptions\QuizAttempts;

final class InvalidQuestionResponse extends QuizAttemptException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
