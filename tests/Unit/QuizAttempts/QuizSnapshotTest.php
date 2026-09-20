<?php

namespace Tests\Unit\QuizAttempts;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizAttempts\QuizSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_an_ordered_internal_snapshot(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Linux']);
        $second = Question::factory()->for($quiz)->create([
            'text' => 'Explain the difference?',
            'position' => 2,
            'type' => QuestionType::Text,
            'max_points' => 4,
        ]);
        $first = Question::factory()->for($quiz)->create(['text' => 'First?', 'position' => 1, 'max_points' => 3]);
        Answer::factory()->for($first)->create(['text' => 'Wrong', 'position' => 2]);
        $correct = Answer::factory()->correct()->for($first)->create(['text' => 'Correct', 'position' => 1]);

        $snapshot = app(QuizSnapshot::class)->make($quiz);

        $this->assertSame('Linux', $snapshot['quiz']['title']);
        $this->assertSame(['First?', 'Explain the difference?'], array_column($snapshot['questions'], 'text'));
        $this->assertSame($correct->uuid, $snapshot['questions'][0]['grading_config']['correct_answer_uuid']);
        $this->assertSame(['single_choice', 'text'], array_column($snapshot['questions'], 'type'));
        $this->assertSame([3, 4], array_column($snapshot['questions'], 'max_points'));
        $this->assertSame(['Correct', 'Wrong'], array_column($snapshot['questions'][0]['public_config']['answers'], 'text'));
        $this->assertArrayNotHasKey('is_correct', $snapshot['questions'][0]['public_config']['answers'][0]);
        $this->assertSame(['max_length' => 5000], $snapshot['questions'][1]['public_config']);
        $this->assertSame(['criteria' => null], $snapshot['questions'][1]['grading_config']);
    }
}
