<?php

namespace App\Queries\User\QuizAttempts;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

final class GetQuizAttemptQuery
{
    public function firstOrFail(User $user, Quiz $quiz): QuizAttempt
    {
        return QuizAttempt::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($quiz)
            ->with('answers')
            ->firstOrFail()
            ->setRelation('quiz', $quiz);
    }
}
