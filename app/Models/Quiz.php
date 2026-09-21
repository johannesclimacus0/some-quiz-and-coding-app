<?php

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Carbon\CarbonImmutable;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable|null $due_at
 * @property int $content_version
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, QuizAttempt> $attempts
 * @property-read int|null $attempts_count
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read Collection<int, Question> $questions
 * @property-read int|null $questions_count
 *
 * @method static \Database\Factories\QuizFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereContentVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['title', 'description', 'due_at'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $casts = [
        'due_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::softDeleted(function (Quiz $quiz): void {
            $deletedAt = $quiz->deleted_at;
            $questionIds = $quiz->questions()->pluck('questions.id');

            if ($questionIds->isEmpty()) {
                return;
            }

            Answer::query()
                ->whereIn('question_id', $questionIds)
                ->update([
                    'deleted_by_parent' => true,
                    'deleted_at' => $deletedAt,
                    'updated_at' => $deletedAt,
                ]);

            Question::query()
                ->whereKey($questionIds)
                ->update([
                    'deleted_by_parent' => true,
                    'deleted_at' => $deletedAt,
                    'updated_at' => $deletedAt,
                ]);
        });

        static::restoring(function (Quiz $quiz): void {
            $questionIds = Question::query()
                ->withTrashed()
                ->where('quiz_id', $quiz->getKey())
                ->where('deleted_by_parent', true)
                ->pluck('id');

            Answer::query()
                ->withTrashed()
                ->whereIn('question_id', $questionIds)
                ->where('deleted_by_parent', true)
                ->update([
                    'deleted_by_parent' => false,
                    'deleted_at' => null,
                ]);

            Question::query()
                ->withTrashed()
                ->whereKey($questionIds)
                ->update([
                    'deleted_by_parent' => false,
                    'deleted_at' => null,
                ]);
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
