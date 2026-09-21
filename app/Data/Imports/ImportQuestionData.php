<?php

namespace App\Data\Imports;

use App\Enums\QuestionType;

final readonly class ImportQuestionData
{
    public function __construct(
        public string $text,
        public int $position,
        public QuestionType $type,
        public int $maxPoints,
        public array $answers,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        $answers = [];

        $type = empty($data['answers'] ?? [])
            ? QuestionType::Text
            : QuestionType::from($data['type'] ?? QuestionType::SingleChoice->value);

        foreach (array_values($data['answers'] ?? []) as $position => $answer) {
            $answers[] = ImportAnswerData::fromArray($answer, $position);
        }

        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? $defaultPosition),
            type: $type,
            maxPoints: (int) ($data['max_points'] ?? 1),
            answers: $answers,
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
