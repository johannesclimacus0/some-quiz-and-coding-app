<?php

namespace App\Data\Imports;

final readonly class ImportAnswerData
{
    public function __construct(
        public string $text,
        public int $position,
        public bool $isCorrect,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? $defaultPosition),
            isCorrect: $data['is_correct'],
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'position' => $this->position,
            'is_correct' => $this->isCorrect,
        ];
    }
}
