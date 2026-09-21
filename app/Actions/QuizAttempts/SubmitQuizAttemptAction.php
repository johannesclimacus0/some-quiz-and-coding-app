<?php

namespace App\Actions\QuizAttempts;

use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\AttemptAlreadySubmitted;
use App\Exceptions\QuizAttempts\AttemptIncomplete;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Questions\QuestionTypeRegistry;
use App\Services\QuizAttempts\QuizDeadline;
use Illuminate\Support\Facades\DB;

final class SubmitQuizAttemptAction
{
    public function __construct(
        private readonly QuizDeadline $deadline,
        private readonly QuestionTypeRegistry $questionTypes,
        private readonly FinalizeQuizAttemptAction $finalize,
    ) {}

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

            $questions = collect($attempt->snapshot['questions'])->keyBy('uuid');

            foreach ($attempt->answers as $answer) {
                $question = $questions->get($answer->question_uuid);

                if ($question === null) {
                    throw new AttemptIncomplete;
                }

                $handler = $this->questionTypes->for(QuestionType::from($question['type']));

                if (!$handler->isComplete($answer->response)) {
                    throw new AttemptIncomplete;
                }

                $answer->update($handler->initialGrading($question, $answer->response));
            }

            $attempt->update([
                'submitted_at' => now(),
            ]);
            $attempt->load('answers');
            $this->finalize->handle($attempt);

            return $attempt->refresh()->load('answers')->setRelation('quiz', $lockedQuiz);
        });
    }
}
