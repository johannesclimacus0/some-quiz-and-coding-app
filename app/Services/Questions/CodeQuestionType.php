<?php

namespace App\Services\Questions;

use App\Enums\AnswerGradingStatus;
use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\InvalidQuestionResponse;
use App\Models\Question;
use App\Services\ProgrammingLanguages\ProgrammingLanguageFactoryRegistry;
use LogicException;

final class CodeQuestionType implements QuestionTypeHandler
{
    private const MAX_LENGTH = 20000;

    public function __construct(
        private readonly ProgrammingLanguageFactoryRegistry $languages,
    ) {}

    public function type(): QuestionType
    {
        return QuestionType::Code;
    }

    public function makeSnapshot(Question $question): array
    {
        $language = $question->programming_language
            ?? throw new LogicException('Code question has no programming language');

        return [
            'public_config' => [
                ...$this->languages->make($language)->toArray(),
                'max_length' => self::MAX_LENGTH,
            ],
            'grading_config' => [
                'criteria' => null,
            ],
        ];
    }

    public function assertValidResponse(array $response, array $questionSnapshot): void
    {
        $code = $response['code'] ?? null;

        if (array_keys($response) !== ['code'] || !is_string($code)) {
            throw new InvalidQuestionResponse('response.code', 'Передан некорректный код');
        }

        if (trim($code) === '') {
            throw new InvalidQuestionResponse('response.code', 'Введите код');
        }

        if (mb_strlen($code) > $questionSnapshot['public_config']['max_length']) {
            throw new InvalidQuestionResponse('response.code', 'Код превышает допустимую длину');
        }
    }

    public function isComplete(array $response): bool
    {
        return is_string($response['code'] ?? null)
            && trim($response['code']) !== '';
    }

    public function initialGrading(array $questionSnapshot, array $response): array
    {
        return [
            'grading_status' => AnswerGradingStatus::PendingManual,
            'awarded_points' => null,
        ];
    }

    public function isManual(): bool
    {
        return true;
    }
}
