<?php

namespace App\Data\Imports;

use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;

final readonly class ImportQuestionData
{
    public function __construct(
        public string $text,
        public int $position,
        public QuestionType $type,
        public ?ProgrammingLanguage $programmingLanguage,
        public int $maxPoints,
        public array $answers,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        $answers = [];

        $type = isset($data['type'])
            ? QuestionType::from($data['type'])
            : (empty($data['answers'] ?? []) ? QuestionType::Text : QuestionType::SingleChoice);

        foreach (array_values($data['answers'] ?? []) as $position => $answer) {
            $answers[] = ImportAnswerData::fromArray($answer, $position);
        }

        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? $defaultPosition),
            type: $type,
            programmingLanguage: isset($data['programming_language'])
                ? ProgrammingLanguage::from($data['programming_language'])
                : null,
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
            'programming_language' => $this->programmingLanguage,
            'max_points' => $this->maxPoints,
        ];
    }
}
