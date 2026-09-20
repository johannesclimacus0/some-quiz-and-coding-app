<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;
use LogicException;

class TextQuestionType implements QuestionTypeHandler
{
    public function type(): QuestionType
    {
        return QuestionType::Text;
    }

    public function makeSnapshot(Question $question): array
    {
        return [
            'public_config' => [
                'max_length' => 5000,
            ],
            'grading_config' => [
                'criteria' => null,
            ],
        ];
    }

    public function assertValidResponse(array $response, array $questionSnapshot): void
    {
        throw new LogicException('Not implemented');
    }

    public function isComplete(array $response): bool
    {
        return is_string($response['text'] ?? null)
            && trim($response['text']) !== '';
    }
}
