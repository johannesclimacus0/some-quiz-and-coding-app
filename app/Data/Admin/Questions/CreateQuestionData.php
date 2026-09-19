<?php

namespace App\Data\Admin\Questions;

final readonly class CreateQuestionData
{
    public function __construct(
        public string $text,
        public int $position = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'],
            position: (int) ($data['position'] ?? 0),
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
