<?php

namespace App\Services\Questions;

use App\Enums\AnswerGradingStatus;
use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\InvalidQuestionResponse;
use App\Models\Question;

final class TextQuestionType implements QuestionTypeHandler
{
    private const MAX_LENGTH = 5000;

    public function type(): QuestionType
    {
        return QuestionType::Text;
    }

    public function makeSnapshot(Question $question): array
    {
        return [
            'public_config' => [
                'max_length' => self::MAX_LENGTH,
            ],
            'grading_config' => [
                'criteria' => null,
            ],
        ];
    }

    public function assertValidResponse(array $response, array $questionSnapshot): void
    {
        $text = $response['text'] ?? null;

        if (array_keys($response) !== ['text'] || !is_string($text)) {
            throw new InvalidQuestionResponse('response.text', 'Передан некорректный текстовый ответ');
        }

        if (trim($text) === '') {
            throw new InvalidQuestionResponse('response.text', 'Введите текстовый ответ');
        }

        $maxLength = $questionSnapshot['public_config']['max_length'];

        if (mb_strlen($text) > $maxLength) {
            throw new InvalidQuestionResponse(
                'response.text',
                'Invalid length',
            );
        }
    }

    public function isComplete(array $response): bool
    {
        return is_string($response['text'] ?? null)
            && trim($response['text']) !== '';
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
