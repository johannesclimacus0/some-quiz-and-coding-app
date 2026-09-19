<?php

namespace App\Data\Admin\Quizzes;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

final readonly class UpdateQuizData
{
    private function __construct(
        public ?string $title,
        public ?string $description,
        public ?CarbonImmutable $dueAt,
        private array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            dueAt: isset($data['due_at']) ? CarbonImmutable::parse($data['due_at']) : null,
            fields: array_keys($data),
        );
    }

    public function toArray(): array
    {
        return Arr::only([
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->dueAt,
        ], $this->fields);
    }
}
