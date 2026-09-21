<?php

namespace App\Actions\Admin\QuizAttempts;

use App\Actions\QuizAttempts\FinalizeQuizAttemptAction;
use App\Data\Admin\QuizAttempts\GradeQuizAttemptAnswerData;
use App\Enums\AnswerGradingStatus;
use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\GradeVersionConflict;
use App\Exceptions\QuizAttempts\QuestionNotInAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use App\Services\Questions\QuestionTypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GradeQuizAttemptAnswerAction
{
    public function __construct(
        private readonly QuestionTypeRegistry $questionTypes,
        private readonly FinalizeQuizAttemptAction $finalize,
    ) {}

    public function handle(User $grader, Quiz $quiz, QuizAttempt $attempt, string $questionUuid, GradeQuizAttemptAnswerData $data): QuizAttempt
    {
        return DB::transaction(function () use ($grader, $quiz, $attempt, $questionUuid, $data): QuizAttempt {
            $lockedAttempt = QuizAttempt::query()
                ->whereKey($attempt->getKey())
                ->whereBelongsTo($quiz)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->submitted_at === null) {
                throw ValidationException::withMessages(['attempt' => 'Попытка ещё не отправлена.']);
            }

            $question = collect($lockedAttempt->snapshot['questions'])->firstWhere('uuid', $questionUuid);

            if ($question === null) {
                throw new QuestionNotInAttempt;
            }

            $handler = $this->questionTypes->for(QuestionType::from($question['type']));

            if (!$handler->isManual()) {
                throw ValidationException::withMessages(['question' => 'Этот вопрос оценивается автоматически.']);
            }

            if ($data->awardedPoints > $question['max_points']) {
                throw ValidationException::withMessages([
                    'awarded_points' => "Баллов должно быть от 0 до {$question['max_points']}.",
                ]);
            }

            $answer = QuizAttemptAnswer::query()
                ->whereBelongsTo($lockedAttempt, 'attempt')
                ->where('question_uuid', $questionUuid)
                ->lockForUpdate()
                ->firstOrFail();

            if ($answer->grading_version !== $data->expectedVersion) {
                throw new GradeVersionConflict;
            }

            $answer->update([
                'awarded_points' => $data->awardedPoints,
                'feedback' => $data->feedback,
                'graded_by' => $grader->getKey(),
                'graded_at' => now(),
                'grading_status' => AnswerGradingStatus::Graded,
                'grading_version' => $answer->grading_version + 1,
            ]);

            $lockedAttempt->load('answers');
            $this->finalize->handle($lockedAttempt);

            return $lockedAttempt->refresh()->load(['user', 'quiz', 'answers.grader']);
        });
    }
}
