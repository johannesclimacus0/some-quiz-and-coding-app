<?php

namespace App\Data\Admin\Questions;

use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;

final readonly class CreateQuestionData
{
    public function __construct(
        public string $text,
        public int $position = 0,
        public QuestionType $type = QuestionType::SingleChoice,
        public ?ProgrammingLanguage $programmingLanguage = null,
        public int $maxPoints = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? 0),
            type: QuestionType::from($data['type'] ?? QuestionType::SingleChoice->value),
            programmingLanguage: isset($data['programming_language'])
                ? ProgrammingLanguage::from($data['programming_language'])
                : null,
            maxPoints: (int) ($data['max_points'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'position' => $this->position,
            'type' => $this->type,
            'programming_language' => $this->programmingLanguage,
            'max_points' => $this->maxPoints,
        ];
    }
}
