<?php

namespace App\Data\Admin\Questions;

use App\Enums\QuestionType;

final readonly class CreateQuestionData
{
    public function __construct(
        public string $text,
        public int $position = 0,
        public QuestionType $type = QuestionType::SingleChoice,
        public int $maxPoints = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? 0),
            type: QuestionType::from($data['type'] ?? QuestionType::SingleChoice->value),
            maxPoints: (int) ($data['max_points'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'position' => $this->position,
            'type' => $this->type,
            'max_points' => $this->maxPoints,
        ];
    }
}
