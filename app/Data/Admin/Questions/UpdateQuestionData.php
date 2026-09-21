<?php

namespace App\Data\Admin\Questions;

use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
use Illuminate\Support\Arr;

final readonly class UpdateQuestionData
{
    private function __construct(
        public ?string $text,
        public ?int $position,
        public ?QuestionType $type,
        public ?ProgrammingLanguage $programmingLanguage,
        public ?int $maxPoints,
        private array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            type: isset($data['type']) ? QuestionType::from($data['type']) : null,
            programmingLanguage: isset($data['programming_language'])
                ? ProgrammingLanguage::from($data['programming_language'])
                : null,
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
            'programming_language' => $this->programmingLanguage,
            'max_points' => $this->maxPoints,
        ], $this->fields);
    }
}
