<?php

namespace App\Models;

use Database\Factories\QuizAttemptAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quiz_attempt_id
 * @property string $question_uuid
 * @property string $answer_uuid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QuizAttempt $attempt
 *
 * @method static \Database\Factories\QuizAttemptAnswerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereAnswerUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereQuestionUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereQuizAttemptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttemptAnswer whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['quiz_attempt_id', 'question_uuid', 'answer_uuid'])]
class QuizAttemptAnswer extends Model
{
    /** @use HasFactory<QuizAttemptAnswerFactory> */
    use HasFactory;

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }
}
