<?php

namespace App\Actions\QuizAttempts;

use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\AttemptAlreadySubmitted;
use App\Exceptions\QuizAttempts\QuestionNotInAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Questions\QuestionTypeRegistry;
use App\Services\QuizAttempts\QuizDeadline;
use Illuminate\Support\Facades\DB;

final class SaveQuizAttemptAnswerAction
{
    public function __construct(
        private readonly QuizDeadline $deadline,
        private readonly QuestionTypeRegistry $questionTypes,
    ) {}

    public function handle(User $user, Quiz $quiz, string $questionUuid, array $response): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz, $questionUuid, $response): QuizAttempt {
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

            $this->questionTypes
                ->for(QuestionType::from($question['type']))
                ->assertValidResponse($response, $question);

            $attempt->answers()->updateOrCreate(
                ['question_uuid' => $questionUuid],
                ['response' => $response]
            );

            return $attempt->load('answers')->setRelation('quiz', $lockedQuiz);
        });
    }
}
