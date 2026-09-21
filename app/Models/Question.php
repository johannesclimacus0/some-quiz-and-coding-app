<?php

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $quiz_id
 * @property string $text
 * @property int $position
 * @property QuestionType $type
 * @property int $max_points
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $deleted_by_parent
 * @property-read Collection<int, Answer> $answers
 * @property-read int|null $answers_count
 * @property-read Quiz|null $quiz
 *
 * @method static \Database\Factories\QuestionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereDeletedByParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereMaxPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Question withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['text', 'position', 'type', 'max_points'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $casts = [
        'type' => QuestionType::class,
        'max_points' => 'integer',
    ];

    protected static function booted(): void
    {
        static::softDeleted(function (Question $question): void {
            $question->answers()->update([
                'deleted_by_parent' => true,
                'deleted_at' => $question->deleted_at,
                'updated_at' => $question->deleted_at,
            ]);
        });

        static::restoring(function (Question $question): void {
            Answer::query()
                ->withTrashed()
                ->where('question_id', $question->getKey())
                ->where('deleted_by_parent', true)
                ->update([
                    'deleted_by_parent' => false,
                    'deleted_at' => null,
                ]);
        });
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
