<?php

namespace Tests\Feature\QuizAttempt;

use App\Actions\QuizAttempts\SaveQuizAttemptAnswerAction;
use App\Actions\QuizAttempts\SubmitQuizAttemptAction;
use App\Exceptions\QuizAttempts\QuizDeadlineExpired;
use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizAttempts\QuizDeadline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class QuizAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_requires_an_active_group_assignment(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        $this->assertTrue(Gate::forUser($user)->allows('participate', $quiz));

        $group->delete();
        $this->assertFalse(Gate::forUser($user)->allows('participate', $quiz));
    }

    public function test_unassigned_quiz_is_forbidden(): void
    {
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows(
            'participate',
            Quiz::factory()->create(),
        ));
    }

    public function test_expired_quiz_rejects_changes(): void
    {
        $this->expectException(QuizDeadlineExpired::class);

        app(QuizDeadline::class)->ensureOpen(Quiz::factory()->create(['due_at' => now()->subSecond()]));
    }

    public function test_saving_an_answer_rechecks_a_stale_quiz_inside_the_transaction(): void
    {
        $attempt = QuizAttempt::factory()->create();
        $staleQuiz = Quiz::query()->findOrFail($attempt->quiz_id);
        Quiz::query()->whereKey($staleQuiz)->update(['due_at' => now()->subMinute()]);

        $this->expectException(QuizDeadlineExpired::class);

        app(SaveQuizAttemptAnswerAction::class)->handle(
            $attempt->user,
            $staleQuiz,
            (string) str()->uuid(),
            ['answer_uuid' => (string) str()->uuid()],
        );
    }

    public function test_submitting_rechecks_a_stale_quiz_inside_the_transaction(): void
    {
        $attempt = QuizAttempt::factory()->create();
        $staleQuiz = Quiz::query()->findOrFail($attempt->quiz_id);
        Quiz::query()->whereKey($staleQuiz)->update(['due_at' => now()->subMinute()]);

        $this->expectException(QuizDeadlineExpired::class);

        app(SubmitQuizAttemptAction::class)->handle($attempt->user, $staleQuiz);
    }
}
