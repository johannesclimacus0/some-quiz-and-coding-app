<?php

namespace App\Parsers;

use App\Contracts\QuizParser;
use App\Data\Imports\ImportQuizData;
use App\Services\Imports\QuizImportValidator;
use InvalidArgumentException;

class MarkdownQuizParser implements QuizParser
{
    public function __construct(
        private readonly QuizImportValidator $validator,
    ) {}

    public function isFormatSupported(string $format): bool
    {
        return in_array(strtolower($format), ['md', 'markdown'], true);
    }

    /**
     * @return iterable<ImportQuizData>
     */
    public function parse(string $contents): iterable
    {
        $lines = preg_split('/\R/u', trim($contents));

        if ($lines === false || $lines === ['']) {
            throw new InvalidArgumentException('Markdown-файл пуст или не может быть прочитан');
        }

        $currentQuiz = null;
        $currentQuestion = null;

        foreach ($lines as $index => $sourceLine) {
            $lineNumber = $index + 1;
            $line = trim($sourceLine);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^#\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuiz !== null) {
                    yield $this->makeQuizData($currentQuiz, $currentQuestion);
                }

                $currentQuiz = [
                    'title' => trim($matches[1]),
                    'description' => null,
                    'due_at' => null,
                    'questions' => [],
                ];
                $currentQuestion = null;

                continue;
            }

            if (preg_match('/^##\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuiz === null) {
                    throw new InvalidArgumentException("Вопрос в строке $lineNumber указан до заголовка квиза");
                }

                if ($currentQuestion !== null) {
                    $currentQuiz['questions'][] = $currentQuestion;
                }

                $currentQuestion = [
                    'text' => trim($matches[1]),
                    'answers' => [],
                ];

                continue;
            }

            if (str_starts_with($line, '>')) {
                if ($currentQuiz === null) {
                    throw new InvalidArgumentException("Описание в строке $lineNumber указано до заголовка квиза");
                }

                if ($currentQuestion !== null) {
                    throw new InvalidArgumentException("Описание квиза в строке $lineNumber должно находиться до вопросов");
                }

                $descriptionLine = trim(substr($line, 1));
                $currentQuiz['description'] = $currentQuiz['description'] === null
                    ? $descriptionLine
                    : $currentQuiz['description'] . "\n" . $descriptionLine;

                continue;
            }

            if (preg_match('/^@type\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuestion === null) {
                    throw new InvalidArgumentException("Тип в строке $lineNumber указан до вопроса");
                }

                $currentQuestion['type'] = trim($matches[1]);

                continue;
            }

            if (preg_match('/^@language\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuestion === null) {
                    throw new InvalidArgumentException("Язык в строке $lineNumber указан до вопроса");
                }

                $currentQuestion['programming_language'] = trim($matches[1]);

                continue;
            }

            if (preg_match('/^@max_points\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuestion === null) {
                    throw new InvalidArgumentException("Максимальный балл в строке $lineNumber указан до вопроса");
                }

                $currentQuestion['max_points'] = trim($matches[1]);

                continue;
            }

            if (preg_match('/^@due_at\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuiz === null) {
                    throw new InvalidArgumentException("Срок в строке $lineNumber указан до заголовка квиза");
                }

                if ($currentQuestion !== null) {
                    throw new InvalidArgumentException("Срок квиза в строке $lineNumber должен находиться до вопросов");
                }

                $dueAt = trim($matches[1]);
                $currentQuiz['due_at'] = strtolower($dueAt) === 'null' ? null : $dueAt;

                continue;
            }

            if (preg_match('/^([*-])\s+(.+)$/u', $line, $matches) === 1) {
                if ($currentQuestion === null) {
                    throw new InvalidArgumentException("Ответ в строке $lineNumber указан до вопроса");
                }

                $currentQuestion['answers'][] = [
                    'text' => trim($matches[2]),
                    'is_correct' => $matches[1] === '-',
                ];

                continue;
            }

            throw new InvalidArgumentException("Неизвестный синтаксис Markdown в строке $lineNumber: $line");
        }

        if ($currentQuiz === null) {
            throw new InvalidArgumentException('Markdown не содержит заголовка квиза');
        }

        yield $this->makeQuizData($currentQuiz, $currentQuestion);
    }

    private function makeQuizData(array $quiz, ?array $question): ImportQuizData
    {
        if ($question !== null) {
            $quiz['questions'][] = $question;
        }

        return ImportQuizData::fromArray(
            $this->validator->validate($quiz),
        );
    }
}
