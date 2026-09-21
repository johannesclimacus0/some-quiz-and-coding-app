<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use LogicException;

final class QuestionTypeRegistry
{
    private array $handlers;

    public function __construct(
        SingleChoiceQuestionType $singleChoice,
        TextQuestionType $text,
    ) {
        $this->handlers = [
            $singleChoice->type()->value => $singleChoice,
            $text->type()->value => $text,
        ];
    }

    public function for(QuestionType $questionType): QuestionTypeHandler
    {
        return $this->handlers[$questionType->value]
            ?? throw new LogicException('Unsupported handler type');
    }
}
