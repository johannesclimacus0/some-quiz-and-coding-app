<?php

namespace Tests\Feature\QuizAttempt;

use App\Enums\AnswerGradingStatus;
use App\Enums\AttemptGradingStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GradeQuizAttemptAnswerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_award_zero_points_and_finalize_a_text_attempt(): void
    {
        [$admin, $attempt, $questionUuid, $url] = $this->pendingTextAttempt();

        $this->actingAs($admin)->putJson($url, [
            'awarded_points' => 0,
            'feedback' => 'Нужно раскрыть аргументацию.',
            'expected_version' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result.earned_points', 0)
            ->assertJsonPath('data.result.max_points', 4)
            ->assertJsonPath('data.result.percentage', 0)
            ->assertJsonPath('data.questions.0.awarded_points', 0)
            ->assertJsonPath('data.questions.0.feedback', 'Нужно раскрыть аргументацию.')
            ->assertJsonPath('data.questions.0.grading_version', 1);

        $this->assertDatabaseHas('quiz_attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_uuid' => $questionUuid,
            'awarded_points' => 0,
            'grading_status' => AnswerGradingStatus::Graded->value,
        ]);
    }

    public function test_score_range_and_stale_version_are_rejected(): void
    {
        [$admin, $attempt, $questionUuid, $url] = $this->pendingTextAttempt();
        $this->actingAs($admin)->putJson($url, [
            'awarded_points' => 5,
            'expected_version' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('awarded_points');

        $this->putJson($url, ['awarded_points' => 2, 'expected_version' => 0])->assertOk();
        $this->putJson($url, ['awarded_points' => 3, 'expected_version' => 0])->assertConflict();
    }

    private function pendingTextAttempt(): array
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $questionUuid = (string) Str::uuid();
        $attempt = QuizAttempt::factory()->for($user)->for($quiz)->create([
            'snapshot' => [
                'quiz' => ['uuid' => $quiz->uuid, 'title' => $quiz->title, 'description' => null],
                'questions' => [[
                    'uuid' => $questionUuid,
                    'type' => 'text',
                    'text' => 'Объясните решение.',
                    'position' => 0,
                    'max_points' => 4,
                    'public_config' => ['max_length' => 5000],
                    'grading_config' => ['criteria' => null],
                ]],
            ],
            'submitted_at' => now(),
            'max_points' => 4,
            'grading_status' => AttemptGradingStatus::Pending,
        ]);
        QuizAttemptAnswer::factory()->for($attempt, 'attempt')->create([
            'question_uuid' => $questionUuid,
            'response' => ['text' => 'Мой ответ'],
            'grading_status' => AnswerGradingStatus::PendingManual,
        ]);

        return [$admin, $attempt, $questionUuid, "/api/admin/quizzes/{$quiz->uuid}/attempts/{$attempt->uuid}/answers/{$questionUuid}/grade"];
    }
}
