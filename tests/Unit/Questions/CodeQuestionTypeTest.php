<?php

namespace Tests\Unit\Questions;

use App\Enums\AnswerGradingStatus;
use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Services\Questions\CodeQuestionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeQuestionTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_public_code_snapshot_and_marks_response_for_manual_grading(): void
    {
        $question = Question::factory()->create([
            'type' => QuestionType::Code,
            'programming_language' => ProgrammingLanguage::Cpp,
        ]);
        $handler = app(CodeQuestionType::class);
        $snapshot = ['max_points' => 3, ...$handler->makeSnapshot($question)];

        $this->assertSame('cpp', $snapshot['public_config']['language']);
        $this->assertSame('C++', $snapshot['public_config']['label']);
        $this->assertSame('cpp', $snapshot['public_config']['editor_id']);
        $this->assertSame('cpp', $snapshot['public_config']['file_extension']);
        $this->assertSame(20000, $snapshot['public_config']['max_length']);
        $this->assertArrayNotHasKey('criteria', $snapshot['public_config']);
        $this->assertTrue($handler->isComplete(['code' => 'echo 1;']));
        $this->assertSame([
            'grading_status' => AnswerGradingStatus::PendingManual,
            'awarded_points' => null,
        ], $handler->initialGrading($snapshot, ['code' => 'echo 1;']));
    }
}
