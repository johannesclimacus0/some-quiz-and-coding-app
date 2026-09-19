<?php

namespace App\Actions\QuizAttempts;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

final class GetQuizAttemptAction
{
    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        return QuizAttempt::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($quiz)
            ->with('answers')
            ->firstOrFail()
            ->setRelation('quiz', $quiz);
    }
}
