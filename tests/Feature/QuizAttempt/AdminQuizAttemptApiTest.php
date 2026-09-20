<?php

namespace Tests\Feature\QuizAttempt;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminQuizAttemptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_attempts_and_inspect_selected_answers(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'student@example.org']);
        $quiz = Quiz::factory()->create();
        $questionUuid = (string) Str::uuid();
        $correctUuid = (string) Str::uuid();
        $wrongUuid = (string) Str::uuid();
        $attempt = QuizAttempt::factory()->for($user)->for($quiz)->create([
            'snapshot' => [
                'quiz' => ['uuid' => $quiz->uuid, 'title' => $quiz->title, 'description' => null],
                'questions' => [[
                    'uuid' => $questionUuid,
                    'type' => 'single_choice',
                    'text' => 'Question?',
                    'position' => 0,
                    'max_points' => 1,
                    'grading_config' => [
                        'correct_answer_uuid' => $correctUuid,
                    ],
                    'public_config' => [
                        'answers' => [
                            ['uuid' => $correctUuid, 'text' => 'Correct', 'position' => 0],
                            ['uuid' => $wrongUuid, 'text' => 'Wrong', 'position' => 1],
                        ],
                    ],
                ]],
            ],
            'submitted_at' => now(),
            'correct_answers' => 0,
            'total_questions' => 1,
        ]);
        QuizAttemptAnswer::factory()->for($attempt, 'attempt')->create([
            'question_uuid' => $questionUuid,
            'response' => ['answer_uuid' => $wrongUuid],
        ]);
        $base = '/api/admin/quizzes/' . $quiz->uuid . '/attempts';

        $this->actingAs($admin)->getJson($base)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.user.email', 'student@example.org')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.result.percentage', 0);

        $this->getJson($base . '/' . $attempt->uuid)
            ->assertOk()
            ->assertJsonPath('data.questions.0.state', 'incorrect')
            ->assertJsonPath('data.questions.0.answers.0.is_correct', true)
            ->assertJsonPath('data.questions.0.answers.0.is_selected', false)
            ->assertJsonPath('data.questions.0.answers.1.is_correct', false)
            ->assertJsonPath('data.questions.0.answers.1.is_selected', true);
    }

    public function test_attempt_must_belong_to_the_quiz_and_user_must_be_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();
        $relatedAttempt = QuizAttempt::factory()->for($quiz)->create();
        $foreignAttempt = QuizAttempt::factory()->create();
        $base = '/api/admin/quizzes/' . $quiz->uuid . '/attempts';

        $this->actingAs($admin)->getJson($base . '/' . $foreignAttempt->uuid)->assertNotFound();

        $this->actingAs(User::factory()->create());
        $this->getJson($base)->assertForbidden();
        $this->getJson($base . '/' . $relatedAttempt->uuid)->assertForbidden();
    }
}
