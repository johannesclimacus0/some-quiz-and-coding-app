<?php

namespace Tests\Feature\QuizAttempt;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizAttemptRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_stores_snapshot_answers_and_relations(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $questionUuid = (string) Str::uuid();
        $answerUuid = (string) Str::uuid();
        $attempt = QuizAttempt::factory()->for($user)->for($quiz)->create([
            'snapshot' => ['quiz' => ['title' => 'Snapshot'], 'questions' => []],
            'started_at' => now(),
            'total_questions' => 2,
        ]);
        QuizAttemptAnswer::factory()->for($attempt, 'attempt')->create([
            'question_uuid' => $questionUuid,
            'response' => ['answer_uuid' => $answerUuid],
        ]);

        $this->assertSame($user->id, $attempt->user->id);
        $this->assertSame($quiz->id, $attempt->quiz->id);
        $this->assertSame('Snapshot', $attempt->snapshot['quiz']['title']);
        $this->assertNotNull($attempt->started_at);
        $this->assertCount(1, $attempt->answers);
        $this->assertSame(['answer_uuid' => $answerUuid], $attempt->answers->first()->response);
        $this->assertCount(1, $user->quizAttempts);
        $this->assertCount(1, $quiz->attempts);
    }

    public function test_user_cannot_have_two_attempts_for_the_same_quiz(): void
    {
        $attempt = QuizAttempt::factory()->create();

        $this->expectException(QueryException::class);
        QuizAttempt::factory()->for($attempt->user)->for($attempt->quiz)->create();
    }

    public function test_attempt_cannot_store_two_answers_for_the_same_question(): void
    {
        $attempt = QuizAttempt::factory()->create();
        $questionUuid = (string) Str::uuid();
        QuizAttemptAnswer::factory()->for($attempt, 'attempt')->create(['question_uuid' => $questionUuid]);

        $this->expectException(QueryException::class);
        QuizAttemptAnswer::factory()->for($attempt, 'attempt')->create(['question_uuid' => $questionUuid]);
    }

    public function test_percentage_is_calculated_once_and_handles_an_empty_attempt(): void
    {
        $attempt = QuizAttempt::factory()->create([
            'earned_points' => 1,
            'max_points' => 3,
        ]);
        $emptyAttempt = QuizAttempt::factory()->create([
            'earned_points' => 0,
            'max_points' => 0,
        ]);

        $this->assertSame(33, $attempt->percentage());
        $this->assertNull($emptyAttempt->percentage());
    }
}
