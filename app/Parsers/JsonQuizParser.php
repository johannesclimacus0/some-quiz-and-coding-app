<?php

namespace App\Parsers;

use App\Contracts\QuizParser;
use App\Data\Imports\ImportQuizData;
use App\Services\Imports\QuizImportValidator;
use InvalidArgumentException;
use JsonException;

class JsonQuizParser implements QuizParser
{
    public function __construct(
        private readonly QuizImportValidator $validator,
    ) {}

    public function isFormatSupported(string $format): bool
    {
        return strtolower($format) === 'json';
    }

    /**
     * @return iterable<ImportQuizData>
     */
    public function parse(string $contents): iterable
    {
        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Некорректный JSON', previous: $e);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Корнем JSON должен быть объект');
        }

        $quizzes = $data['quizzes'] ?? null;

        if (!is_array($quizzes)) {
            throw new InvalidArgumentException('JSON не содержит массив квизов');
        }

        if (!array_is_list($quizzes)) {
            throw new InvalidArgumentException('Поле quizzes должно быть списком');
        }

        if ($quizzes === []) {
            throw new InvalidArgumentException('Файл должен содержать хотя бы один квиз');
        }

        foreach ($quizzes as $index => $quiz) {
            if (!is_array($quiz) || array_is_list($quiz)) {
                throw new InvalidArgumentException("Элемент quizzes с индексом $index должен быть объектом");
            }

            $validated = $this->validator->validate($quiz);

            yield ImportQuizData::fromArray($validated);
        }
    }
}
