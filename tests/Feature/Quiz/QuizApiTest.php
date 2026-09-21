<?php

namespace Tests\Feature\Quiz;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_a_quiz_and_its_questions_and_answers(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quizUuid = $this->postJson('/api/admin/quizzes', ['title' => 'Quiz', 'description' => 'Original'])
            ->assertCreated()->json('data.uuid');
        $quizUrl = '/api/admin/quizzes/' . $quizUuid;
        $questionUuid = $this->postJson($quizUrl . '/questions', ['text' => 'Question'])
            ->assertCreated()->assertJsonPath('data.position', 0)->json('data.uuid');
        $questionUrl = $quizUrl . '/questions/' . $questionUuid;
        $answerUuid = $this->postJson($questionUrl . '/answers', ['text' => 'Answer', 'is_correct' => true])
            ->assertCreated()->assertJsonPath('data.is_correct', false)->json('data.uuid');
        $answerUrl = $questionUrl . '/answers/' . $answerUuid;

        $this->patchJson($quizUrl, ['title' => 'Changed'])
            ->assertOk()->assertJsonPath('data.description', 'Original');
        $this->patchJson($quizUrl, ['description' => null, 'due_at' => null])
            ->assertOk()->assertJsonPath('data.description', null);
        $this->patchJson($questionUrl, ['position' => 2])->assertOk()->assertJsonPath('data.text', 'Question');
        $this->patchJson($answerUrl, ['text' => 'Changed answer', 'position' => 0])->assertOk();
        $this->putJson($questionUrl . '/correct-answer', ['answer_uuid' => $answerUuid])
            ->assertOk()->assertJsonPath('data.is_correct', true);
        $this->getJson($quizUrl)->assertOk()
            ->assertJsonPath('data.questions.0.answers.0.text', 'Changed answer');
        $this->getJson('/api/admin/quizzes')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($quizUrl . '/questions')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($questionUrl . '/answers')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($answerUrl)->assertOk();
        $this->getJson($questionUrl)->assertOk();

        $this->deleteJson($quizUrl)->assertNoContent();
        $this->assertSoftDeleted('quizzes', ['uuid' => $quizUuid]);
        $this->assertSoftDeleted('questions', ['uuid' => $questionUuid]);
        $this->assertSoftDeleted('answers', ['uuid' => $answerUuid]);
        $this->getJson($quizUrl)->assertNotFound();
        $this->postJson($quizUrl . '/questions', ['text' => 'Hidden'])->assertNotFound();
    }

    public function test_non_admins_cannot_read_or_modify_quizzes(): void
    {
        $quiz = Quiz::factory()->create();
        $this->getJson('/api/admin/quizzes')->assertUnauthorized();
        $this->postJson('/api/admin/quizzes', ['title' => 'Forbidden'])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/admin/quizzes')->assertForbidden();
        $this->postJson('/api/admin/quizzes', ['title' => 'Forbidden'])->assertForbidden();
        $this->patchJson('/api/admin/quizzes/' . $quiz->uuid, ['title' => 'Forbidden'])->assertForbidden();
        $this->deleteJson('/api/admin/quizzes/' . $quiz->uuid)->assertForbidden();
    }

    public function test_nested_bindings_reject_unrelated_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $foreignQuestion = Question::factory()->create();
        $foreignAnswer = Answer::factory()->for($foreignQuestion)->create();
        $base = '/api/admin/quizzes/' . $quiz->uuid . '/questions/';

        $this->getJson($base . $foreignQuestion->uuid)->assertNotFound();
        $this->patchJson($base . $foreignQuestion->uuid, ['text' => 'Invalid'])->assertNotFound();
        $this->deleteJson($base . $foreignQuestion->uuid)->assertNotFound();
        $this->postJson($base . $foreignQuestion->uuid . '/answers', ['text' => 'Invalid'])->assertNotFound();
        $this->patchJson($base . $question->uuid . '/answers/' . $foreignAnswer->uuid, ['text' => 'Invalid'])->assertNotFound();
        $this->putJson($base . $question->uuid . '/correct-answer', ['answer_uuid' => $foreignAnswer->uuid])
            ->assertUnprocessable()->assertJsonValidationErrors('answer_uuid');
    }

    public function test_validation_and_correct_answer_replacement(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson('/api/admin/quizzes', [])->assertUnprocessable()->assertJsonValidationErrors('title');
        $question = Question::factory()->create();
        $previous = Answer::factory()->for($question)->create(['is_correct' => true]);
        $answer = Answer::factory()->for($question)->create(['is_correct' => false]);
        $url = '/api/admin/quizzes/' . $question->quiz->uuid . '/questions/' . $question->uuid;
        $this->postJson($url . '/answers', ['text' => '', 'position' => -1])->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'position']);
        $this->putJson($url . '/correct-answer', ['answer_uuid' => $answer->uuid])->assertOk();
        $this->assertFalse($previous->fresh()->is_correct);
        $this->assertTrue($answer->fresh()->is_correct);
        $this->deleteJson($url . '/answers/' . $answer->uuid)->assertNoContent();
        $this->putJson($url . '/correct-answer', ['answer_uuid' => $answer->uuid])->assertUnprocessable();
        $this->deleteJson($url)->assertNoContent();
        $this->assertSoftDeleted($previous);
    }

    public function test_admin_can_create_a_text_question_but_cannot_change_a_live_question_type(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();
        $this->actingAs($admin);
        $url = '/api/admin/quizzes/' . $quiz->uuid . '/questions';

        $questionUuid = $this->postJson($url, [
            'text' => 'Объясните решение.',
            'type' => 'text',
            'max_points' => 4,
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.max_points', 4)
            ->json('data.uuid');

        QuizAttempt::factory()->for($quiz)->create();

        $this->patchJson($url . '/' . $questionUuid, ['type' => 'single_choice'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_admin_can_create_code_questions_in_supported_languages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $url = '/api/admin/quizzes/' . $quiz->uuid . '/questions';

        foreach (['cpp', 'sql', 'java'] as $position => $language) {
            $this->postJson($url, [
                'text' => "Задание на $language",
                'position' => $position,
                'type' => 'code',
                'programming_language' => $language,
                'max_points' => 5,
            ])
                ->assertCreated()
                ->assertJsonPath('data.type', 'code')
                ->assertJsonPath('data.programming_language', $language);
        }

        $this->postJson($url, ['text' => 'Без языка', 'type' => 'code'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('programming_language');
        $this->postJson($url, [
            'text' => 'Неизвестный язык',
            'type' => 'code',
            'programming_language' => 'python',
        ])->assertUnprocessable()->assertJsonValidationErrors('programming_language');
    }
}
