<?php

namespace App\Services\Questions;

use App\Enums\AnswerGradingStatus;
use App\Enums\QuestionType;
use App\Models\Question;

interface QuestionTypeHandler
{
    public function type(): QuestionType;

    public function makeSnapshot(Question $question): array;

    public function assertValidResponse(array $response, array $questionSnapshot): void;

    public function isComplete(array $response): bool;

    /** @return array{grading_status: AnswerGradingStatus, awarded_points: int|null} */
    public function initialGrading(array $questionSnapshot, array $response): array;

    public function isManual(): bool;
}
