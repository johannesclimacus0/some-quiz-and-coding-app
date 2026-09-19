<?php

namespace App\Data\Admin\Answers;

use Illuminate\Support\Arr;

final readonly class UpdateAnswerData
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
