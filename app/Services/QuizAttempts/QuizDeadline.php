<?php

namespace App\Services\QuizAttempts;

use App\Exceptions\QuizAttempts\QuizDeadlineExpired;
use App\Models\Quiz;

final class QuizDeadline
{
    public function ensureOpen(Quiz $quiz): void
    {
        if ($quiz->due_at?->isPast()) {
            throw new QuizDeadlineExpired;
        }
    }
}
