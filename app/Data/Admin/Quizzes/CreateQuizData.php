<?php

namespace App\Data\Admin\Quizzes;

use Carbon\CarbonImmutable;

final readonly class CreateQuizData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?CarbonImmutable $dueAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            dueAt: isset($data['due_at']) ? CarbonImmutable::parse($data['due_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->dueAt,
        ];
    }
}
