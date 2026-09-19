<?php

namespace Tests\Feature\Quiz;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_a_quiz_soft_deletes_its_questions_and_answers(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answer = Answer::factory()->for($question)->create();

        $quiz->delete();

        $this->assertSoftDeleted($quiz);
        $this->assertSoftDeleted($question);
        $this->assertSoftDeleted($answer);
    }

    public function test_soft_deleting_a_question_soft_deletes_its_answers(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->create();

        $question->delete();

        $this->assertSoftDeleted($question);
        $this->assertSoftDeleted($answer);
    }

    public function test_force_deleting_a_quiz_physically_deletes_its_questions_and_answers(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answer = Answer::factory()->for($question)->create();

        $quiz->forceDelete();

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
    }

    public function test_restoring_a_quiz_restores_descendants_deleted_with_it(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answer = Answer::factory()->for($question)->create();

        $quiz->delete();
        $quiz->restore();

        $this->assertNotSoftDeleted($quiz);
        $this->assertNotSoftDeleted($question);
        $this->assertNotSoftDeleted($answer);
    }

    public function test_restoring_a_question_restores_only_answers_deleted_with_it(): void
    {
        $question = Question::factory()->create();
        $previouslyDeleted = Answer::factory()->for($question)->create();
        $cascadeDeleted = Answer::factory()->for($question)->create();
        $previouslyDeleted->delete();

        $question->delete();
        $question->restore();

        $this->assertNotSoftDeleted($question);
        $this->assertSoftDeleted($previouslyDeleted);
        $this->assertNotSoftDeleted($cascadeDeleted);
    }

    public function test_restoring_a_quiz_preserves_a_question_deleted_before_the_quiz(): void
    {
        $quiz = Quiz::factory()->create();
        $previouslyDeleted = Question::factory()->for($quiz)->create();
        $previouslyDeletedAnswer = Answer::factory()->for($previouslyDeleted)->create();
        $cascadeDeleted = Question::factory()->for($quiz)->create();
        $cascadeDeletedAnswer = Answer::factory()->for($cascadeDeleted)->create();
        $previouslyDeleted->delete();

        $quiz->delete();
        $quiz->restore();

        $this->assertSoftDeleted($previouslyDeleted);
        $this->assertSoftDeleted($previouslyDeletedAnswer);
        $this->assertNotSoftDeleted($cascadeDeleted);
        $this->assertNotSoftDeleted($cascadeDeletedAnswer);
    }
}
