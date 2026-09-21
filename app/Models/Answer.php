<?php

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $question_id
 * @property string $text
 * @property bool $is_correct
 * @property int $position
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $deleted_by_parent
 * @property-read Question|null $question
 *
 * @method static \Database\Factories\AnswerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereDeletedByParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereIsCorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['text', 'position', 'is_correct'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function isCorrect(): bool
    {
        return $this->is_correct;
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
