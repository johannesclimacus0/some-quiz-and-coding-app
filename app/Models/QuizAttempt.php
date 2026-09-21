<?php

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\AttemptGradingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $quiz_id
 * @property array<array-key, mixed> $snapshot
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $correct_answers
 * @property int $total_questions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, QuizAttemptAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read Quiz|null $quiz
 * @property-read User $user
 *
 * @method static \Database\Factories\QuizAttemptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereCorrectAnswers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereTotalQuestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['user_id', 'quiz_id', 'snapshot', 'started_at', 'submitted_at', 'correct_answers', 'total_questions', 'max_points', 'earned_points', 'graded_at', 'grading_status'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory, HasUuidRouteKey;

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'started_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'correct_answers' => 'integer',
            'total_questions' => 'integer',
            'max_points' => 'integer',
            'earned_points' => 'integer',
            'graded_at' => 'immutable_datetime',
            'grading_status' => AttemptGradingStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function status(?Quiz $quiz = null): string
    {
        if ($this->submitted_at !== null) {
            return $this->grading_status === AttemptGradingStatus::Graded ? 'completed' : 'submitted';
        }

        return ($quiz ?? $this->quiz)->due_at?->isPast() ? 'expired' : 'in_progress';
    }

    public function percentage(): ?int
    {
        if ($this->earned_points === null || $this->max_points === 0) {
            return null;
        }

        return (int) round($this->earned_points / $this->max_points * 100);
    }
}
