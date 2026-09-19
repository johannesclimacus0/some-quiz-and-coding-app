<?php

namespace App\Actions\QuizAttempts;

use App\Exceptions\QuizAttempts\AnswerNotInQuestion;
use App\Exceptions\QuizAttempts\AttemptAlreadySubmitted;
use App\Exceptions\QuizAttempts\QuestionNotInAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizAttempts\QuizDeadline;
use Illuminate\Support\Facades\DB;

final class SaveQuizAttemptAnswerAction
{
    public function __construct(private readonly QuizDeadline $deadline) {}

    public function handle(User $user, Quiz $quiz, string $questionUuid, string $answerUuid): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz, $questionUuid, $answerUuid): QuizAttempt {
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

            $question = collect($attempt->snapshot['questions'])->firstWhere('uuid', $questionUuid);

            if (!$question) {
                throw new QuestionNotInAttempt;
            }

            if (!collect($question['answers'])->contains('uuid', $answerUuid)) {
                throw new AnswerNotInQuestion;
            }

            $attempt->answers()->updateOrCreate(
                ['question_uuid' => $questionUuid],
                ['answer_uuid' => $answerUuid]
            );

            return $attempt->load('answers')->setRelation('quiz', $lockedQuiz);
        });
    }
}
