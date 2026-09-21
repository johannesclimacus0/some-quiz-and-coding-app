<?php

namespace Tests\Feature\QuizAttempt;

use App\Enums\AnswerGradingStatus;
use App\Enums\AttemptGradingStatus;
use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
use App\Models\Group;
use App\Models\Question;
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

    public function test_code_answer_can_be_submitted_reviewed_and_manually_graded(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['due_at' => null]);
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        Question::factory()->for($quiz)->create([
            'type' => QuestionType::Code,
            'programming_language' => ProgrammingLanguage::Java,
            'max_points' => 6,
        ]);

        $userPath = "/api/quizzes/{$quiz->uuid}/attempt";
        $started = $this->actingAs($user)->postJson($userPath)
            ->assertCreated()
            ->assertJsonPath('data.snapshot.questions.0.type', 'code')
            ->assertJsonPath('data.snapshot.questions.0.public_config.language', 'java')
            ->assertJsonPath('data.snapshot.questions.0.public_config.label', 'Java')
            ->assertJsonPath('data.snapshot.questions.0.public_config.editor_id', 'java')
            ->json('data');
        $questionUuid = $started['snapshot']['questions'][0]['uuid'];
        $code = 'class Main { public static void main(String[] args) {} }';

        $this->putJson("{$userPath}/answers/{$questionUuid}", [
            'response' => ['code' => $code],
        ])->assertOk()->assertJsonPath("data.responses.{$questionUuid}.code", $code);

        $submitted = $this->postJson("{$userPath}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.result.grading_status', 'pending')
            ->json('data');

        $adminPath = "/api/admin/quizzes/{$quiz->uuid}/attempts/{$submitted['uuid']}";
        $this->actingAs($admin)->getJson($adminPath)
            ->assertOk()
            ->assertJsonPath('data.questions.0.type', 'code')
            ->assertJsonPath('data.questions.0.response.code', $code)
            ->assertJsonPath('data.questions.0.public_config.editor_id', 'java')
            ->assertJsonPath('data.questions.0.state', 'pending_manual');

        $this->putJson("{$adminPath}/answers/{$questionUuid}/grade", [
            'awarded_points' => 5,
            'feedback' => 'Решение верное, но добавьте вывод результата.',
            'expected_version' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result.earned_points', 5)
            ->assertJsonPath('data.result.max_points', 6);

        $this->actingAs($user)->getJson($userPath)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result.earned_points', 5)
            ->assertJsonPath("data.responses.{$questionUuid}.awarded_points", 5)
            ->assertJsonPath("data.responses.{$questionUuid}.feedback", 'Решение верное, но добавьте вывод результата.');
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
