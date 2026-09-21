<?php

namespace Tests\Feature\QuizAttempt;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveQuizAttemptAnswerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_answer_is_created_updated_and_restored(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attempt = $this->actingAs($user)->postJson($base)->assertCreated()->json('data');
        $question = $attempt['snapshot']['questions'][0];
        $url = $base . '/answers/' . $question['uuid'];

        $this->putJson($url, ['response' => ['answer_uuid' => $question['public_config']['answers'][0]['uuid']]])
            ->assertOk()->assertJsonPath('data.responses.' . $question['uuid'] . '.answer_uuid', $question['public_config']['answers'][0]['uuid']);
        $this->putJson($url, ['response' => ['answer_uuid' => $question['public_config']['answers'][1]['uuid']]])
            ->assertOk()->assertJsonPath('data.responses.' . $question['uuid'] . '.answer_uuid', $question['public_config']['answers'][1]['uuid']);
        $this->getJson($base)
            ->assertOk()->assertJsonPath('data.responses.' . $question['uuid'] . '.answer_uuid', $question['public_config']['answers'][1]['uuid']);
        $this->assertDatabaseCount('quiz_attempt_answers', 1);
        $this->assertSame(
            ['answer_uuid' => $question['public_config']['answers'][1]['uuid']],
            QuizAttemptAnswer::query()->sole()->response,
        );
    }

    public function test_question_and_answer_must_belong_to_the_snapshot(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attempt = $this->actingAs($user)->postJson($base)->json('data');
        $question = $attempt['snapshot']['questions'][0];

        $this->putJson($base . '/answers/' . Str::uuid(), ['response' => ['answer_uuid' => $question['public_config']['answers'][0]['uuid']]])
            ->assertUnprocessable();
        $this->putJson($base . '/answers/' . $question['uuid'], ['response' => ['answer_uuid' => (string) Str::uuid()]])
            ->assertUnprocessable()->assertJsonValidationErrors('response.answer_uuid');

        $foreignAnswer = Answer::factory()->for(Question::factory()->for($quiz))->create();
        $this->putJson($base . '/answers/' . $question['uuid'], ['response' => ['answer_uuid' => $foreignAnswer->uuid]])
            ->assertUnprocessable()->assertJsonValidationErrors('response.answer_uuid');
        $this->assertDatabaseCount('quiz_attempt_answers', 0);
    }

    public function test_invalid_response_shapes_are_rejected_without_saving(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attempt = $this->actingAs($user)->postJson($base)->assertCreated()->json('data');
        $question = $attempt['snapshot']['questions'][0];
        $url = $base . '/answers/' . $question['uuid'];
        $answerUuid = $question['public_config']['answers'][0]['uuid'];

        foreach ([
            [[], 'response'],
            [['answer_uuid' => $answerUuid], 'response'],
            [['response' => []], 'response'],
            [['response' => 'invalid'], 'response'],
            [['response' => ['answer_uuid' => 'invalid']], 'response.answer_uuid'],
            [['response' => ['answer_uuid' => null]], 'response.answer_uuid'],
            [['response' => ['text' => 'unexpected']], 'response.answer_uuid'],
            [['response' => ['answer_uuid' => $answerUuid, 'awarded_points' => 100]], 'response.answer_uuid'],
        ] as [$payload, $errorKey]) {
            $this->putJson($url, $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($errorKey);
        }

        $this->assertDatabaseCount('quiz_attempt_answers', 0);
    }

    public function test_completed_expired_and_revoked_attempts_reject_changes(): void
    {
        [$user, $quiz, $group] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attemptData = $this->actingAs($user)->postJson($base)->json('data');
        $question = $attemptData['snapshot']['questions'][0];
        $url = $base . '/answers/' . $question['uuid'];
        $payload = ['response' => ['answer_uuid' => $question['public_config']['answers'][0]['uuid']]];

        QuizAttempt::query()->where('uuid', $attemptData['uuid'])->update(['submitted_at' => now(), 'correct_answers' => 0]);
        $this->putJson($url, $payload)->assertConflict();

        QuizAttempt::query()->where('uuid', $attemptData['uuid'])->update(['submitted_at' => null, 'correct_answers' => null]);
        $quiz->update(['due_at' => now()->subMinute()]);
        $this->putJson($url, $payload)->assertConflict();

        $quiz->update(['due_at' => null]);
        $group->users()->detach($user);
        $this->putJson($url, $payload)->assertForbidden();
    }

    public function test_text_response_is_saved_and_public_config_does_not_reveal_grading_criteria(): void
    {
        [$user, $quiz] = $this->assignedTextQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attempt = $this->actingAs($user)->postJson($base)->assertCreated();
        $question = $attempt->json('data.snapshot.questions.0');
        $url = $base . '/answers/' . $question['uuid'];

        $attempt
            ->assertJsonPath('data.snapshot.questions.0.type', 'text')
            ->assertJsonPath('data.snapshot.questions.0.public_config.max_length', 5000);
        $this->assertStringNotContainsString('criteria', $attempt->getContent());

        $this->putJson($url, ['response' => ['text' => 'Текстовый ответ']])
            ->assertOk()
            ->assertJsonPath('data.responses.' . $question['uuid'] . '.text', 'Текстовый ответ');

        foreach ([
            [[], 'response'],
            [['text' => '   '], 'response.text'],
            [['text' => str_repeat('a', 5001)], 'response.text'],
            [['answer_uuid' => (string) Str::uuid()], 'response.text'],
            [['text' => 'Ответ', 'extra' => true], 'response.text'],
        ] as [$response, $errorKey]) {
            $this->putJson($url, ['response' => $response])
                ->assertUnprocessable()
                ->assertJsonValidationErrors($errorKey);
        }
    }

    private function assignedReadyQuiz(): array
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['due_at' => null]);
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        $question = Question::factory()->for($quiz)->create();
        Answer::factory()->correct()->for($question)->create(['position' => 0]);
        Answer::factory()->for($question)->create(['position' => 1]);

        return [$user, $quiz, $group];
    }

    private function assignedTextQuiz(): array
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['due_at' => null]);
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        Question::factory()->for($quiz)->create([
            'type' => QuestionType::Text,
            'max_points' => 4,
        ]);

        return [$user, $quiz];
    }
}
