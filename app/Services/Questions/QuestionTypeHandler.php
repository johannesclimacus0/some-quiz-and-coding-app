<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;

interface QuestionTypeHandler
{
    public function type(): QuestionType;

    public function makeSnapshot(Question $question): array;

    public function assertValidResponse(array $response, array $questionSnapshot): void;

    public function isComplete(array $response): bool;
}
