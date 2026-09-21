<?php

namespace App\Data\Admin\QuizAttempts;

final readonly class GradeQuizAttemptAnswerData
{
    public function __construct(
        public int $awardedPoints,
        public ?string $feedback,
        public int $expectedVersion,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            awardedPoints: (int) $data['awarded_points'],
            feedback: $data['feedback'] ?? null,
            expectedVersion: (int) $data['expected_version'],
        );
    }
}
