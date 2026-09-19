<?php

namespace Tests\Feature\Quiz;

use App\Actions\Admin\Questions\CreateQuestionAction;
use App\Actions\Admin\Questions\DeleteQuestionAction;
use App\Actions\Admin\Questions\UpdateQuestionAction;
use App\Data\Admin\Questions\CreateQuestionData;
use App\Data\Admin\Questions\UpdateQuestionData;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_increments_version_on_create(): void
    {
        $quiz = Quiz::factory()->create();
        $quizData = CreateQuestionData::fromArray([
            'text' => 'Test Question',
            'position' => 1,
        ]);

        app(CreateQuestionAction::class)->handle($quiz, $quizData);
        $quiz->refresh();

        $this->assertSame(2, $quiz->content_version);

        $quizData = CreateQuestionData::fromArray([
            'text' => 'Test Question',
            'position' => 2,
        ]);

        app(CreateQuestionAction::class)->handle($quiz, $quizData);
        $quiz->refresh();

        $this->assertSame(3, $quiz->content_version);
    }

    public function test_quiz_increments_version_on_update(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $updateData = UpdateQuestionData::fromArray([
            'text' => 'Test Question',
            'position' => 5,
        ]);

        app(UpdateQuestionAction::class)->handle($quiz, $question, $updateData);
        $quiz->refresh();
        $question->refresh();

        $this->assertSame('Test Question', $question->text);
        $this->assertSame(5, $question->position);
        $this->assertSame(2, $quiz->content_version);
    }

    public function test_quiz_increments_version_on_delete(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();

        app(DeleteQuestionAction::class)->handle($quiz, $question);

        $quiz->refresh();
        $question->refresh();

        $this->assertSoftDeleted($question);
        $this->assertSame(2, $quiz->content_version);
    }

    public function test_quiz_increments_version_when_an_answer_is_created(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();

        $this->postJson("/api/admin/quizzes/{$quiz->uuid}/questions/{$question->uuid}/answers", [
            'text' => 'New answer',
            'position' => 1,
        ])->assertCreated();

        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'text' => 'New answer',
            'position' => 1,
        ]);

        $this->assertSame(2, $quiz->fresh()->content_version);
    }

    public function test_quiz_increments_version_when_an_answer_is_updated(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answer = Answer::factory()->for($question)->create();

        $this->patchJson("/api/admin/quizzes/{$quiz->uuid}/questions/{$question->uuid}/answers/{$answer->uuid}", [
            'text' => 'Updated answer',
            'position' => 5,
        ])->assertOk();

        $this->assertDatabaseHas('answers', [
            'id' => $answer->id,
            'text' => 'Updated answer',
            'position' => 5,
        ]);
        $this->assertSame(2, $quiz->fresh()->content_version);
    }

    public function test_quiz_increments_version_when_an_answer_is_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answer = Answer::factory()->for($question)->create();

        $this->deleteJson("/api/admin/quizzes/{$quiz->uuid}/questions/{$question->uuid}/answers/{$answer->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('answers', ['id' => $answer->id]);
        $this->assertSame(2, $quiz->fresh()->content_version);
    }

    public function test_quiz_increments_version_when_the_correct_answer_changes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $previousAnswer = Answer::factory()->for($question)->create(['is_correct' => true]);
        $newAnswer = Answer::factory()->for($question)->create(['is_correct' => false]);

        $this->putJson("/api/admin/quizzes/{$quiz->uuid}/questions/{$question->uuid}/correct-answer", [
            'answer_uuid' => $newAnswer->uuid,
        ])->assertOk();

        $this->assertFalse($previousAnswer->fresh()->is_correct);
        $this->assertTrue($newAnswer->fresh()->is_correct);
        $this->assertSame(2, $quiz->fresh()->content_version);
    }

    public function test_content_version_cannot_be_mass_assigned(): void
    {
        $quiz = Quiz::factory()->create();
        $initialVersion = $quiz->content_version;

        $quiz->fill(['content_version' => $initialVersion + 1]);

        $this->assertSame($initialVersion, $quiz->content_version);
    }
}
