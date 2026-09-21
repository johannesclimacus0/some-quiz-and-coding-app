<?php

namespace App\Actions\QuizAttempts;

use App\Enums\AnswerGradingStatus;
use App\Enums\AttemptGradingStatus;
use App\Models\QuizAttempt;

final class FinalizeQuizAttemptAction
{
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing('answers');

        if ($attempt->answers->contains(fn ($answer): bool => $answer->grading_status !== AnswerGradingStatus::Graded)) {
            $attempt->update([
                'grading_status' => AttemptGradingStatus::Pending,
                'earned_points' => null,
                'graded_at' => null,
            ]);

            return $attempt;
        }

        $attempt->update([
            'grading_status' => AttemptGradingStatus::Graded,
            'earned_points' => $attempt->answers->sum('awarded_points'),
            'graded_at' => now(),
        ]);

        return $attempt;
    }
}
