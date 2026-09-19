<?php

namespace App\Data\Imports;

final readonly class ImportQuestionData
{
    public function __construct(
        public string $text,
        public int $position,
        public array $answers,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        $answers = [];

        foreach (array_values($data['answers'] ?? []) as $position => $answer) {
            $answers[] = ImportAnswerData::fromArray($answer, $position);
        }

        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? $defaultPosition),
            answers: $answers,
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'position' => $this->position,
        ];
    }
}
