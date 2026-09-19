<?php

namespace Tests\Feature\Quiz;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuizImportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_json_and_markdown_files(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/api/admin/quizzes/import', [
            'file' => UploadedFile::fake()->createWithContent(
                'quizzes.json',
                file_get_contents(base_path('tests/Fixtures/Imports/example.json')),
            ),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.quizzes', 1)
            ->assertJsonPath('data.questions', 1)
            ->assertJsonPath('data.answers', 2);

        $this->post('/api/admin/quizzes/import', [
            'file' => UploadedFile::fake()->createWithContent(
                'quizzes.md',
                file_get_contents(base_path('tests/Fixtures/Imports/example.md')),
            ),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.quizzes', 1)
            ->assertJsonPath('data.questions', 2)
            ->assertJsonPath('data.answers', 5);

        $this->assertSame(2, Quiz::query()->count());
        $this->assertSame(3, Question::query()->count());
        $this->assertSame(7, Answer::query()->count());
        $this->assertSame(3, Answer::query()->where('is_correct', true)->count());
    }

    public function test_import_is_atomic_when_a_later_quiz_is_invalid(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $contents = json_encode([
            'quizzes' => [
                [
                    'title' => 'Valid quiz',
                    'questions' => [[
                        'text' => 'Valid question',
                        'answers' => [
                            ['text' => 'Correct', 'is_correct' => true],
                            ['text' => 'Wrong', 'is_correct' => false],
                        ],
                    ]],
                ],
                [
                    'title' => 'Invalid quiz',
                    'questions' => [[
                        'text' => 'Question without a correct answer',
                        'answers' => [
                            ['text' => 'Wrong one', 'is_correct' => false],
                            ['text' => 'Wrong two', 'is_correct' => false],
                        ],
                    ]],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->post('/api/admin/quizzes/import', [
            'file' => UploadedFile::fake()->createWithContent('quizzes.json', $contents),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('questions.0.answers');

        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_import_requires_admin_access_and_a_supported_file(): void
    {
        $file = fn () => UploadedFile::fake()->createWithContent('quizzes.json', '{"quizzes":[]}');

        $this->post('/api/admin/quizzes/import', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->post('/api/admin/quizzes/import', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->post('/api/admin/quizzes/import', [
                'file' => UploadedFile::fake()->createWithContent('quizzes.txt', 'text'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }
}
