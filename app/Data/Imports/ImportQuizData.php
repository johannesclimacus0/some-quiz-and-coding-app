<?php

namespace App\Data\Imports;

use Carbon\CarbonImmutable;

final readonly class ImportQuizData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?CarbonImmutable $dueAt,
        public array $questions,
    ) {}

    public static function fromArray(array $data): self
    {
        $questions = [];

        foreach (array_values($data['questions'] ?? []) as $position => $question) {
            $questions[] = ImportQuestionData::fromArray($question, $position);
        }

        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            dueAt: isset($data['due_at']) ? CarbonImmutable::parse($data['due_at']) : null,
            questions: $questions,
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
