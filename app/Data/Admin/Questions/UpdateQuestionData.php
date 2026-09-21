<?php

namespace App\Data\Admin\Questions;

use App\Enums\QuestionType;
use Illuminate\Support\Arr;

final readonly class UpdateQuestionData
{
    private function __construct(
        public ?string $text,
        public ?int $position,
        public ?QuestionType $type,
        public ?int $maxPoints,
        private array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            type: isset($data['type']) ? QuestionType::from($data['type']) : null,
            maxPoints: isset($data['max_points']) ? (int) $data['max_points'] : null,
            fields: array_keys($data),
        );
    }

    public function toArray(): array
    {
        return Arr::only([
            'text' => $this->text,
            'position' => $this->position,
            'type' => $this->type,
            'max_points' => $this->maxPoints,
        ], $this->fields);
    }
}
