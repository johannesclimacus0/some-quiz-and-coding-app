<?php

namespace Tests\Unit\Imports;

use App\Data\Imports\ImportQuizData;
use App\Parsers\MarkdownQuizParser;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarkdownQuizParserTest extends TestCase
{
    private MarkdownQuizParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = $this->app->make(MarkdownQuizParser::class);
    }

    public function test_it_supports_markdown_formats(): void
    {
        $this->assertTrue($this->parser->isFormatSupported('md'));
        $this->assertTrue($this->parser->isFormatSupported('MARKDOWN'));
        $this->assertFalse($this->parser->isFormatSupported('json'));
    }

    public function test_it_parses_the_markdown_fixture(): void
    {
        $contents = file_get_contents(base_path('tests/Fixtures/Imports/example.md'));

        $quizzes = iterator_to_array($this->parser->parse($contents));

        $this->assertCount(1, $quizzes);
        $this->assertInstanceOf(ImportQuizData::class, $quizzes[0]);
        $this->assertSame('Linux', $quizzes[0]->title);
        $this->assertSame('Основы Linux', $quizzes[0]->description);
        $this->assertNull($quizzes[0]->dueAt);
        $this->assertCount(3, $quizzes[0]->questions);
        $this->assertSame(0, $quizzes[0]->questions[0]->position);
        $this->assertSame(1, $quizzes[0]->questions[1]->position);
        $this->assertSame('ls', $quizzes[0]->questions[0]->answers[0]->text);
        $this->assertTrue($quizzes[0]->questions[0]->answers[0]->isCorrect);
        $this->assertFalse($quizzes[0]->questions[0]->answers[1]->isCorrect);
        $this->assertSame('text', $quizzes[0]->questions[2]->type->value);
        $this->assertSame([], $quizzes[0]->questions[2]->answers);
    }

    public function test_it_parses_multiple_quizzes_and_multiline_descriptions(): void
    {
        $contents = <<<'MD'
            # Первый квиз
            > Первая строка
            > Вторая строка
            ## Вопрос 1
            * Верный
            - Неверный

            # Второй квиз
            @due_at 2027-01-01 12:00:00
            ## Вопрос 2
            - Неверный
            * Верный
            MD;

        $quizzes = iterator_to_array($this->parser->parse($contents));

        $this->assertCount(2, $quizzes);
        $this->assertSame("Первая строка\nВторая строка", $quizzes[0]->description);
        $this->assertSame('Второй квиз', $quizzes[1]->title);
        $this->assertSame('2027-01-01 12:00:00', $quizzes[1]->dueAt?->format('Y-m-d H:i:s'));
    }

    public function test_question_without_answers_becomes_text_question(): void
    {
        $quizzes = iterator_to_array($this->parser->parse(<<<'MD'
# Квиз
## Напишите объяснение
MD));

        $question = $quizzes[0]->questions[0];

        $this->assertSame('text', $question->type->value);
        $this->assertSame([], $question->answers);
    }

    #[DataProvider('invalidSyntaxProvider')]
    public function test_it_reports_invalid_markdown_syntax(string $contents, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        iterator_to_array($this->parser->parse($contents));
    }

    public static function invalidSyntaxProvider(): array
    {
        return [
            'empty document' => ['   ', 'Markdown-файл пуст'],
            'question before quiz' => ["## Вопрос\n* Да\n- Нет", 'Вопрос в строке 1 указан до заголовка квиза'],
            'answer before question' => ["# Квиз\n* Да", 'Ответ в строке 2 указан до вопроса'],
            'metadata after question' => ["# Квиз\n## Вопрос\n@due_at null\n* Да\n- Нет", 'Срок квиза в строке 3 должен находиться до вопросов'],
            'unknown line' => ["# Квиз\nобычный текст", 'Неизвестный синтаксис Markdown в строке 2'],
        ];
    }

    public function test_it_passes_the_parsed_quiz_to_the_validator(): void
    {
        $this->expectException(ValidationException::class);

        iterator_to_array($this->parser->parse(<<<'MD'
# Квиз
## Вопрос
- Первый
- Второй
MD));
    }
}
