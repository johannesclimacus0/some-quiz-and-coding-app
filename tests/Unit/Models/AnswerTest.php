<?php

namespace Tests\Unit\Models;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_whether_the_answer_is_correct(): void
    {
        $correct = Answer::factory()->correct()->create();
        $wrong = Answer::factory()->create();

        $this->assertTrue($correct->isCorrect());
        $this->assertFalse($wrong->isCorrect());
    }
}
