<?php

namespace App\Actions\QuizAttempts;

use App\Exceptions\QuizAttempts\AttemptAlreadySubmitted;
use App\Exceptions\QuizAttempts\AttemptIncomplete;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizAttempts\QuizDeadline;
use Illuminate\Support\Facades\DB;

final class SubmitQuizAttemptAction
{
    public function __construct(private readonly QuizDeadline $deadline) {}

    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz): QuizAttempt {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            $this->deadline->ensureOpen($lockedQuiz);

            $attempt = QuizAttempt::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($lockedQuiz)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->submitted_at !== null) {
                throw new AttemptAlreadySubmitted;
            }

            $attempt->load('answers');

            if ($attempt->answers->count() !== $attempt->total_questions) {
                throw new AttemptIncomplete;
            }

            $selected = $attempt->answers->pluck('response.answer_uuid', 'question_uuid');
            $correct = collect($attempt->snapshot['questions'])->filter(
                fn (array $question): bool => $selected->get($question['uuid']) === $question['grading_config']['correct_answer_uuid']
            )->count();

            $attempt->update([
                'correct_answers' => $correct,
                'submitted_at' => now(),
            ]);

            return $attempt->refresh()->load('answers')->setRelation('quiz', $lockedQuiz);
        });
    }
}
