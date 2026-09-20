<?php

namespace Tests\Feature\QuizAttempt;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmitQuizAttemptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_questions_are_required_before_submission(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $this->actingAs($user)->postJson($base)->assertCreated();

        $this->postJson($base . '/submit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answers');
    }

    public function test_result_uses_the_snapshot_and_cannot_be_submitted_twice(): void
    {
        [$user, $quiz] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $attempt = $this->actingAs($user)->postJson($base)->json('data');
        [$first, $second] = $attempt['snapshot']['questions'];
        $firstCorrect = $first['answers'][0]['uuid'];
        $secondWrong = $second['answers'][1]['uuid'];

        $this->putJson($base . '/answers/' . $first['uuid'], ['response' => ['answer_uuid' => $firstCorrect]])->assertOk();
        $this->putJson($base . '/answers/' . $second['uuid'], ['response' => ['answer_uuid' => $secondWrong]])->assertOk();

        Answer::query()->where('uuid', $firstCorrect)->update(['is_correct' => false]);
        Answer::query()->where('uuid', $first['answers'][1]['uuid'])->update(['is_correct' => true]);

        $response = $this->postJson($base . '/submit')
            ->assertOk()
            ->assertJsonPath('data.correct_answers', 1)
            ->assertJsonPath('data.total_questions', 2)
            ->assertJsonPath('data.percentage', 50);
        $this->assertStringNotContainsString('correct_answer_uuid', $response->getContent());
        $this->assertStringNotContainsString('grading_config', $response->getContent());
        $this->postJson($base . '/submit')->assertConflict();
        $this->getJson('/api/quizzes')->assertOk()
            ->assertJsonPath('data.0.attempt_status', 'completed')
            ->assertJsonPath('data.0.result.percentage', 50);
    }

    public function test_expired_and_revoked_attempts_cannot_be_submitted(): void
    {
        [$user, $quiz, $group] = $this->assignedReadyQuiz();
        $base = '/api/quizzes/' . $quiz->uuid . '/attempt';
        $this->actingAs($user)->postJson($base)->assertCreated();

        $quiz->update(['due_at' => now()->subMinute()]);
        $this->postJson($base . '/submit')->assertConflict();

        $quiz->update(['due_at' => null]);
        $group->users()->detach($user);
        $this->postJson($base . '/submit')->assertForbidden();
    }

    private function assignedReadyQuiz(): array
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['due_at' => null]);
        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);

        foreach ([0, 1] as $position) {
            $question = Question::factory()->for($quiz)->create(['position' => $position]);
            Answer::factory()->correct()->for($question)->create(['position' => 0]);
            Answer::factory()->for($question)->create(['position' => 1]);
        }

        return [$user, $quiz, $group];
    }
}
