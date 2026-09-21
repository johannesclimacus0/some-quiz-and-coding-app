<?php

namespace App\Actions\QuizAttempts;

use App\Exceptions\QuizAttempts\QuizNotReady;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizAttempts\QuizDeadline;
use App\Services\QuizAttempts\QuizSnapshot;
use Illuminate\Support\Facades\DB;

final class StartQuizAttemptAction
{
    public function __construct(
        private readonly QuizDeadline $deadline,
        private readonly QuizSnapshot $snapshot,
    ) {}

    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz): QuizAttempt {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            $this->deadline->ensureOpen($lockedQuiz);

            if (!$lockedQuiz->questions()->exists()) {
                throw new QuizNotReady;
            }

            $existing = QuizAttempt::query()
                ->whereBelongsTo($lockedUser)
                ->whereBelongsTo($lockedQuiz)
                ->first();

            if ($existing) {
                return $existing->load('answers')->setRelation('quiz', $lockedQuiz);
            }

            $snapshot = $this->snapshot->make($lockedQuiz);
            $attempt = QuizAttempt::query()->create([
                'user_id' => $lockedUser->getKey(),
                'quiz_id' => $lockedQuiz->getKey(),
                'snapshot' => $snapshot,
                'started_at' => now(),
                'total_questions' => count($snapshot['questions']),
                'max_points' => array_sum(array_column($snapshot['questions'], 'max_points')),
            ]);

            return $attempt->load('answers')->setRelation('quiz', $lockedQuiz);
        });
    }
}
