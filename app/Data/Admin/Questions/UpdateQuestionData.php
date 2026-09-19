<?php

namespace App\Data\Admin\Questions;

use Illuminate\Support\Arr;

final readonly class UpdateQuestionData
{
    private function __construct(
        public ?string $text,
        public ?int $position,
        private array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            fields: array_keys($data),
        );
    }

    public function toArray(): array
    {
        return Arr::only([
            'text' => $this->text,
            'position' => $this->position,
        ], $this->fields);
    }
}
