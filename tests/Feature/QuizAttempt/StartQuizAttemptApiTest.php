<?php

namespace Tests\Feature\QuizAttempt;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartQuizAttemptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_starts_once_and_restores_a_safe_attempt(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $url = '/api/quizzes/' . $quiz->uuid;

        $this->actingAs($user)->getJson('/api/quizzes')
            ->assertOk()->assertJsonPath('data.0.attempt_status', 'not_started');
        $this->getJson($url)
            ->assertOk()->assertJsonPath('data.attempt', null)->assertJsonPath('data.questions_count', 1);

        $started = $this->postJson($url . '/attempt')
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'status',
                    'snapshot' => [
                        'quiz' => ['uuid', 'title', 'description'],
                        'questions' => [[
                            'uuid',
                            'type',
                            'text',
                            'position',
                            'max_points',
                            'public_config' => [
                                'answers' => [['uuid', 'text', 'position']],
                            ],
                        ]],
                    ],
                    'responses',
                    'result',
                    'started_at',
                ],
            ])
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonCount(1, 'data.snapshot.questions')
            ->assertJsonPath('data.responses', []);
        $this->assertStringNotContainsString('correct_answer_uuid', $started->getContent());
        $this->assertStringNotContainsString('grading_config', $started->getContent());

        $uuid = $started->json('data.uuid');
        $this->postJson($url . '/attempt')->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->getJson($url . '/attempt')->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->getJson('/api/quizzes')->assertOk()->assertJsonPath('data.0.attempt_status', 'in_progress');
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_user_cannot_start_unassigned_expired_or_unready_quiz(): void
    {
        $user = User::factory()->create();
        $unassigned = Quiz::factory()->create();
        $this->actingAs($user)->postJson('/api/quizzes/' . $unassigned->uuid . '/attempt')->assertForbidden();

        [$assignedUser, $expired] = $this->assignedReadyQuiz(['due_at' => now()->subMinute()]);
        $this->actingAs($assignedUser)->postJson('/api/quizzes/' . $expired->uuid . '/attempt')->assertConflict();

        $group = Group::factory()->create();
        $unready = Quiz::factory()->create();
        $group->users()->attach($assignedUser);
        $group->quizzes()->attach($unready);
        $this->postJson('/api/quizzes/' . $unready->uuid . '/attempt')->assertConflict();
    }

    private function assignedReadyQuiz(array $quizAttributes = []): array
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create($quizAttributes);
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        $question = Question::factory()->for($quiz)->create(['position' => 0]);
        Answer::factory()->correct()->for($question)->create(['position' => 0]);
        Answer::factory()->for($question)->create(['position' => 1]);

        return [$user, $quiz];
    }
}
