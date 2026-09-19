<?php

namespace Tests\Unit\Imports;

use App\Data\Imports\ImportQuizData;
use App\Parsers\JsonQuizParser;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JsonQuizParserTest extends TestCase
{
    private JsonQuizParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = $this->app->make(JsonQuizParser::class);
    }

    public function test_it_supports_the_json_format(): void
    {
        $this->assertTrue($this->parser->isFormatSupported('json'));
        $this->assertTrue($this->parser->isFormatSupported('JSON'));
        $this->assertFalse($this->parser->isFormatSupported('md'));
    }

    public function test_it_parses_quizzes_and_assigns_missing_positions(): void
    {
        $contents = json_encode([
            'quizzes' => [[
                'title' => 'Linux',
                'description' => 'Основы Linux',
                'questions' => [
                    [
                        'text' => 'Какая команда выводит файлы?',
                        'answers' => [
                            ['text' => 'ls', 'is_correct' => true],
                            ['text' => 'cd', 'is_correct' => false],
                        ],
                    ],
                    [
                        'text' => 'Какая команда меняет директорию?',
                        'position' => 7,
                        'answers' => [
                            ['text' => 'ls', 'is_correct' => false],
                            ['text' => 'cd', 'position' => 4, 'is_correct' => true],
                        ],
                    ],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $quizzes = iterator_to_array($this->parser->parse($contents));

        $this->assertCount(1, $quizzes);
        $this->assertInstanceOf(ImportQuizData::class, $quizzes[0]);
        $this->assertSame('Linux', $quizzes[0]->title);
        $this->assertSame(0, $quizzes[0]->questions[0]->position);
        $this->assertSame(7, $quizzes[0]->questions[1]->position);
        $this->assertSame(0, $quizzes[0]->questions[0]->answers[0]->position);
        $this->assertSame(4, $quizzes[0]->questions[1]->answers[1]->position);
        $this->assertTrue($quizzes[0]->questions[0]->answers[0]->isCorrect);
    }

    public function test_it_reports_invalid_json(): void
    {
        try {
            iterator_to_array($this->parser->parse('{"quizzes":}'));
            $this->fail('Invalid JSON was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Некорректный JSON', $exception->getMessage());
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    #[DataProvider('invalidDocumentProvider')]
    public function test_it_rejects_invalid_document_structures(string $contents, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        iterator_to_array($this->parser->parse($contents));
    }

    public static function invalidDocumentProvider(): array
    {
        return [
            'scalar root' => ['42', 'Корнем JSON должен быть объект'],
            'missing quizzes' => ['{}', 'JSON не содержит массив квизов'],
            'quizzes object' => ['{"quizzes":{"first":{}}}', 'Поле quizzes должно быть списком'],
            'empty quizzes' => ['{"quizzes":[]}', 'Файл должен содержать хотя бы один квиз'],
            'scalar quiz' => ['{"quizzes":[42]}', 'Элемент quizzes с индексом 0 должен быть объектом'],
            'quiz list' => ['{"quizzes":[[]]}', 'Элемент quizzes с индексом 0 должен быть объектом'],
        ];
    }

    public function test_it_passes_each_quiz_to_the_import_validator(): void
    {
        $this->expectException(ValidationException::class);

        iterator_to_array($this->parser->parse('{"quizzes":[{"title":"Without questions"}]}'));
    }
}
